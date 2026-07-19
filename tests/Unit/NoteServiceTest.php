<?php

declare(strict_types=1);

namespace tests\Unit;

use common\dtos\NoteQueryDto;
use common\dtos\NoteWriteDto;
use common\entities\Category;
use common\entities\Note;
use common\repositories\CategoryRepositoryInterface;
use common\repositories\NoteRepositoryInterface;
use common\repositories\PersistenceException;
use common\services\EventLoggerInterface;
use common\services\NoteService;
use common\services\exceptions\CategoryNotFoundException;
use common\services\exceptions\NotFoundException;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\console\Application;
use yii\db\ActiveQuery;
use yii\db\ColumnSchema;
use yii\db\Connection;
use yii\db\Schema;
use yii\db\TableSchema;
use yii\web\Application as WebApplication;

final class NoteServiceTest extends TestCase
{
    private static Application|WebApplication|null $previousApplication;

    public static function setUpBeforeClass(): void
    {
        self::$previousApplication = Yii::$app;
        Yii::$app = new NoteTestApplication(new NoteTestConnection());
    }

    public static function tearDownAfterClass(): void
    {
        Yii::$app = self::$previousApplication;
    }

    public function testCreateReadUpdateAndDeleteOwnedNoteWithAuditEvents(): void
    {
        $notes = new NoteTestRepository();
        $logger = new NoteTestLogger();
        $service = $this->service($notes, [
            $this->category(1, 'Work'),
            $this->category(2, 'Personal'),
        ], $logger);

        $created = $service->create(10, new NoteWriteDto(1, 'First note', 'Initial content'));

        self::assertSame(1, (int) $created->id);
        self::assertSame(10, (int) $created->user_id);
        self::assertSame(1, (int) $created->category_id);
        self::assertSame('First note', $created->title);
        self::assertSame('Initial content', $created->content);
        self::assertSame($created, $service->get(10, (int) $created->id));

        $updated = $service->update(
            10,
            (int) $created->id,
            new NoteWriteDto(2, 'Updated note', 'Updated content'),
        );

        self::assertSame($created, $updated);
        self::assertSame(2, (int) $updated->category_id);
        self::assertSame('Updated note', $updated->title);
        self::assertSame('Updated content', $updated->content);

        $service->delete(10, (int) $updated->id);

        self::assertNull($notes->findOwnedById((int) $updated->id, 10));
        self::assertSame([
            'note.created',
            'note.viewed',
            'note.updated',
            'note.deleted',
        ], array_column($logger->infoEvents, 'message'));
    }

    public function testAnotherUserCannotReadOwnedNoteAndAttemptIsLogged(): void
    {
        $notes = new NoteTestRepository([
            $this->note(7, 10, 1, 'Private', 'Only the owner can read this.'),
        ]);
        $logger = new NoteTestLogger();
        $service = $this->service($notes, [$this->category(1, 'Work')], $logger);

        try {
            $service->get(99, 7);
            self::fail('A note must only be visible to its owner.');
        } catch (NotFoundException $exception) {
            self::assertSame('Note not found.', $exception->getMessage());
        }

        self::assertSame([
            'message' => 'note.not_found',
            'context' => ['user_id' => 99, 'note_id' => 7],
        ], $logger->warningEvents[0] ?? null);
    }

    public function testCreateRejectsUnknownCategoryWithoutPersistence(): void
    {
        $notes = new NoteTestRepository();
        $logger = new NoteTestLogger();
        $service = $this->service($notes, [$this->category(1, 'Work')], $logger);

        try {
            $service->create(10, new NoteWriteDto(999, 'Unknown category', 'Must not be persisted.'));
            self::fail('Unknown category must be rejected.');
        } catch (CategoryNotFoundException $exception) {
            self::assertSame('Category not found.', $exception->getMessage());
        }

        self::assertSame(0, $notes->countOwned(10, null));
        self::assertSame([
            'message' => 'category.not_found',
            'context' => ['category_id' => 999],
        ], $logger->warningEvents[0] ?? null);
    }

    public function testCreateMapsConcurrentCategoryDeletionToDomainError(): void
    {
        $category = $this->category(1, 'Work');
        $categoryLookup = 0;
        $categories = $this->createStub(CategoryRepositoryInterface::class);
        $categories->method('findById')
            ->willReturnCallback(static function () use (&$categoryLookup, $category): ?Category {
                ++$categoryLookup;

                return $categoryLookup === 1 ? $category : null;
            });
        $persistence = new PersistenceException('Foreign key violation.');
        $notes = $this->createMock(NoteRepositoryInterface::class);
        $notes->expects(self::once())->method('save')->willThrowException($persistence);
        $service = new NoteService($notes, $categories, new NoteTestLogger());

        try {
            $service->create(10, new NoteWriteDto(1, 'Race', 'Category is being deleted.'));
            self::fail('A deleted category must be exposed as a domain error.');
        } catch (CategoryNotFoundException $exception) {
            self::assertSame($persistence, $exception->getPrevious());
            self::assertSame(2, $categoryLookup);
        }
    }

    public function testUpdateRejectsUnknownCategoryWithoutChangingOwnedNote(): void
    {
        $note = $this->note(7, 10, 1, 'Original', 'Original content');
        $notes = new NoteTestRepository([$note]);
        $service = $this->service($notes, [$this->category(1, 'Work')]);

        $this->expectException(CategoryNotFoundException::class);

        try {
            $service->update(10, 7, new NoteWriteDto(999, 'Changed', 'Changed content'));
        } finally {
            self::assertSame(1, (int) $note->category_id);
            self::assertSame('Original', $note->title);
            self::assertSame('Original content', $note->content);
        }
    }

    public function testListAppliesOwnershipCategoryPaginationAndLogsMetrics(): void
    {
        $notes = new NoteTestRepository([
            $this->note(1, 10, 1, 'One', 'Content'),
            $this->note(2, 10, 2, 'Two', 'Content'),
            $this->note(3, 10, 1, 'Three', 'Content'),
            $this->note(4, 20, 1, 'Another user', 'Content'),
            $this->note(5, 10, 1, 'Five', 'Content'),
            $this->note(6, 10, 1, 'Six', 'Content'),
        ]);
        $logger = new NoteTestLogger();
        $service = $this->service($notes, [
            $this->category(1, 'Work'),
            $this->category(2, 'Personal'),
        ], $logger);

        $page = $service->list(10, new NoteQueryDto(categoryId: 1, page: 2, perPage: 2));

        self::assertSame(4, $page->total);
        self::assertSame(2, $page->page);
        self::assertSame(2, $page->perPage);
        self::assertSame([3, 1], array_map(
            static fn (Note $note): int => (int) $note->id,
            $page->items,
        ));
        self::assertSame([
            'user_id' => 10,
            'category_id' => 1,
            'page' => 2,
            'per_page' => 2,
            'returned' => 2,
            'total' => 4,
        ], $logger->infoEvents[0]['context'] ?? null);
    }

    public function testListRejectsUnknownCategoryBeforeQueryingNotes(): void
    {
        $notes = new NoteTestRepository();
        $service = $this->service($notes, [$this->category(1, 'Work')]);

        $this->expectException(CategoryNotFoundException::class);

        try {
            $service->list(10, new NoteQueryDto(categoryId: 999));
        } finally {
            self::assertSame(0, $notes->ownedQueryCount);
        }
    }

    /** @param list<Category> $categories */
    private function service(
        NoteTestRepository $notes,
        array $categories,
        ?NoteTestLogger $logger = null,
    ): NoteService {
        return new NoteService(
            $notes,
            new NoteTestCategoryRepository($categories),
            $logger ?? new NoteTestLogger(),
        );
    }

    private function category(int $id, string $name): Category
    {
        $category = new Category(['name' => $name]);
        $category->setAttributes([
            'id' => $id,
            'created_at' => '2026-07-19 12:00:00+00:00',
            'updated_at' => '2026-07-19 12:00:00+00:00',
        ], false);

        return $category;
    }

    private function note(
        int $id,
        int $userId,
        int $categoryId,
        string $title,
        string $content,
    ): Note {
        $note = new Note([
            'user_id' => $userId,
            'category_id' => $categoryId,
            'title' => $title,
            'content' => $content,
        ]);
        $note->setAttributes([
            'id' => $id,
            'created_at' => '2026-07-19 12:00:00+00:00',
            'updated_at' => '2026-07-19 12:00:00+00:00',
        ], false);

        return $note;
    }
}

final class NoteTestRepository implements NoteRepositoryInterface
{
    /** @var array<int, Note> */
    private array $notes = [];
    private int $nextId = 1;
    public int $ownedQueryCount = 0;

    /** @param list<Note> $notes */
    public function __construct(array $notes = [])
    {
        foreach ($notes as $note) {
            $this->store($note);
        }
    }

    public function findOwnedById(int $id, int $userId): ?Note
    {
        $note = $this->notes[$id] ?? null;

        return $note instanceof Note && (int) $note->user_id === $userId ? $note : null;
    }

    public function findAllOwned(
        int $userId,
        ?int $categoryId,
        int $limit,
        int $offset,
    ): array {
        ++$this->ownedQueryCount;
        $notes = array_values(array_filter(
            $this->notes,
            static fn (Note $note): bool => (int) $note->user_id === $userId
                && ($categoryId === null || (int) $note->category_id === $categoryId),
        ));
        usort(
            $notes,
            static fn (Note $left, Note $right): int => (int) $right->id <=> (int) $left->id,
        );

        return array_slice($notes, $offset, $limit);
    }

    public function countOwned(int $userId, ?int $categoryId): int
    {
        ++$this->ownedQueryCount;

        return count(array_filter(
            $this->notes,
            static fn (Note $note): bool => (int) $note->user_id === $userId
                && ($categoryId === null || (int) $note->category_id === $categoryId),
        ));
    }

    public function findById(int $id): ?Note
    {
        return $this->notes[$id] ?? null;
    }

    public function query(): ActiveQuery
    {
        /** @var ActiveQuery<Note> $query */
        $query = new ActiveQuery(Note::class);

        return $query;
    }

    public function count(): int
    {
        return count($this->notes);
    }

    public function save(Note $note): Note
    {
        $this->store($note);

        return $note;
    }

    public function delete(Note $note): void
    {
        unset($this->notes[(int) $note->id]);
    }

    private function store(Note $note): void
    {
        $id = (int) ($note->id ?: $this->nextId);
        $this->nextId = max($this->nextId, $id + 1);
        $note->setAttributes([
            'id' => $id,
            'created_at' => $note->created_at ?: '2026-07-19 12:00:00+00:00',
            'updated_at' => '2026-07-19 12:00:00+00:00',
        ], false);
        $this->notes[$id] = $note;
    }
}

final class NoteTestCategoryRepository implements CategoryRepositoryInterface
{
    /** @var array<int, Category> */
    private array $categories = [];

    /** @param list<Category> $categories */
    public function __construct(array $categories)
    {
        foreach ($categories as $category) {
            $this->categories[(int) $category->id] = $category;
        }
    }

    public function findById(int $id): ?Category
    {
        return $this->categories[$id] ?? null;
    }

    public function findByName(string $name): ?Category
    {
        foreach ($this->categories as $category) {
            if ($category->name === $name) {
                return $category;
            }
        }

        return null;
    }

    public function findAll(): array
    {
        return array_values($this->categories);
    }

    public function query(): ActiveQuery
    {
        /** @var ActiveQuery<Category> $query */
        $query = new ActiveQuery(Category::class);

        return $query;
    }

    public function count(): int
    {
        return count($this->categories);
    }

    public function save(Category $category): Category
    {
        $this->categories[(int) $category->id] = $category;

        return $category;
    }

    public function delete(Category $category): void
    {
        unset($this->categories[(int) $category->id]);
    }
}

final class NoteTestLogger implements EventLoggerInterface
{
    /** @var list<array{message: string, context: array<string, mixed>}> */
    public array $infoEvents = [];
    /** @var list<array{message: string, context: array<string, mixed>}> */
    public array $warningEvents = [];

    public function info(string $message, array $context = []): void
    {
        $this->infoEvents[] = ['message' => $message, 'context' => $context];
    }

    public function warning(string $message, array $context = []): void
    {
        $this->warningEvents[] = ['message' => $message, 'context' => $context];
    }
}

final class NoteTestApplication extends Application
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getDb(): Connection
    {
        return $this->connection;
    }
}

final class NoteTestConnection extends Connection
{
    private ?NoteTestSchema $testSchema = null;

    public function getSchema(): Schema
    {
        return $this->testSchema ??= new NoteTestSchema(['db' => $this]);
    }
}

final class NoteTestSchema extends Schema
{
    protected function loadTableSchema($name): ?TableSchema
    {
        $definitions = [
            'categories' => ['id', 'name', 'created_at', 'updated_at'],
            'notes' => ['id', 'user_id', 'category_id', 'title', 'content', 'created_at', 'updated_at'],
        ];
        if (!isset($definitions[$name])) {
            return null;
        }

        $columns = [];
        foreach ($definitions[$name] as $columnName) {
            $columns[$columnName] = new ColumnSchema(['name' => $columnName]);
        }

        return new TableSchema([
            'name' => $name,
            'fullName' => $name,
            'primaryKey' => ['id'],
            'columns' => $columns,
        ]);
    }
}
