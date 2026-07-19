<?php

declare(strict_types=1);

namespace tests\Unit;

use DateTimeImmutable;
use application\dto\note\NoteQueryDto;
use application\dto\note\NoteWriteDto;
use application\services\NoteService;
use domain\entities\Category;
use domain\entities\Note;
use domain\exceptions\NotFoundException;
use domain\repositories\CategoryRepositoryInterface;
use domain\repositories\NoteRepositoryInterface;
use domain\services\EventLoggerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class NoteServiceTest extends TestCase
{
    public function testCreateReadUpdateAndDeleteOwnedNote(): void
    {
        $notes = new NoteTestRepository();
        $service = $this->service($notes, [
            new Category(1, 'Work'),
            new Category(2, 'Personal'),
        ]);

        $created = $service->create(10, $this->writeDto(1, 'First note', 'Initial content'));

        self::assertSame(1, $created->id);
        self::assertSame(10, $created->userId);
        self::assertSame(1, $created->categoryId);
        self::assertSame('First note', $created->title);
        self::assertSame('Initial content', $created->content);
        self::assertSame($created, $service->get(10, (int) $created->id));

        $updated = $service->update(
            10,
            (int) $created->id,
            $this->writeDto(2, 'Updated note', 'Updated content'),
        );

        self::assertSame($created->id, $updated->id);
        self::assertSame(2, $updated->categoryId);
        self::assertSame('Updated note', $updated->title);
        self::assertSame('Updated content', $updated->content);

        $service->delete(10, (int) $updated->id);

        self::assertNull($notes->findOwnedById((int) $updated->id, 10));
    }

    public function testAnotherUserCannotReadOwnedNote(): void
    {
        $notes = new NoteTestRepository([
            new Note(7, 10, 1, 'Private', 'Only the owner can read this.'),
        ]);
        $service = $this->service($notes, [new Category(1, 'Work')]);

        $this->expectException(NotFoundException::class);

        $service->get(99, 7);
    }

    public function testCreateRejectsUnknownCategory(): void
    {
        $notes = new NoteTestRepository();
        $service = $this->service($notes, [new Category(1, 'Work')]);
        $dto = $this->writeDto(999, 'Unknown category', 'Must not be persisted.');

        try {
            $service->create(10, $dto);
            self::fail('Unknown category must be rejected.');
        } catch (NotFoundException) {
            self::assertSame(0, $notes->countOwned(10, null));
        }
    }

    public function testListAppliesOwnershipCategoryAndPagination(): void
    {
        $notes = new NoteTestRepository([
            new Note(1, 10, 1, 'One', 'Content'),
            new Note(2, 10, 2, 'Two', 'Content'),
            new Note(3, 10, 1, 'Three', 'Content'),
            new Note(4, 20, 1, 'Another user', 'Content'),
            new Note(5, 10, 1, 'Five', 'Content'),
            new Note(6, 10, 1, 'Six', 'Content'),
        ]);
        $service = $this->service($notes, [
            new Category(1, 'Work'),
            new Category(2, 'Personal'),
        ]);
        $query = $this->queryDto(' 1 ', ' 2 ', ' 2 ');

        $page = $service->list(10, $query);

        self::assertSame(4, $page->total);
        self::assertSame(2, $page->page);
        self::assertSame(2, $page->perPage);
        self::assertSame([3, 1], array_map(
            static fn (Note $note): ?int => $note->id,
            $page->items,
        ));
    }

    public function testWriteCommandNormalizesTextOutsideHttp(): void
    {
        $service = $this->service(new NoteTestRepository(), [new Category(1, 'Work')]);

        $note = $service->create(10, $this->writeDto(1, '  Title  ', '  Content  '));

        self::assertSame('Title', $note->title);
        self::assertSame('Content', $note->content);
    }

    public function testWriteCommandRejectsWhitespaceOnlyContent(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->writeDto(1, 'Title', '   ');
    }

    /**
     * @param list<Category> $categories
     */
    private function service(NoteTestRepository $notes, array $categories): NoteService
    {
        return new NoteService(
            $notes,
            new NoteTestCategoryRepository($categories),
            new NoteTestLogger(),
        );
    }

    private function writeDto(int $categoryId, string $title, string $content): NoteWriteDto
    {
        return new NoteWriteDto($categoryId, $title, $content);
    }

    private function queryDto(mixed $categoryId, mixed $page, mixed $perPage): NoteQueryDto
    {
        return new NoteQueryDto(
            categoryId: $categoryId === null ? null : (int) $categoryId,
            page: (int) $page,
            perPage: (int) $perPage,
        );
    }
}

final class NoteTestRepository implements NoteRepositoryInterface
{
    /** @var array<int, Note> */
    private array $notes = [];
    private int $nextId = 1;

    /**
     * @param list<Note> $notes
     */
    public function __construct(array $notes = [])
    {
        foreach ($notes as $note) {
            $this->save($note);
        }
    }

    public function findOwnedById(int $id, int $userId): ?Note
    {
        $note = $this->notes[$id] ?? null;

        return $note !== null && $note->userId === $userId ? $note : null;
    }

    public function findAllOwned(
        int $userId,
        ?int $categoryId,
        int $limit,
        int $offset,
    ): array {
        $notes = array_values(array_filter(
            $this->notes,
            static fn (Note $note): bool => $note->userId === $userId
                && ($categoryId === null || $note->categoryId === $categoryId),
        ));
        usort($notes, static fn (Note $left, Note $right): int => $right->id <=> $left->id);

        return array_slice($notes, $offset, $limit);
    }

    public function countOwned(int $userId, ?int $categoryId): int
    {
        return count(array_filter(
            $this->notes,
            static fn (Note $note): bool => $note->userId === $userId
                && ($categoryId === null || $note->categoryId === $categoryId),
        ));
    }

    public function save(Note $note): Note
    {
        $id = $note->id ?? $this->nextId++;
        $this->nextId = max($this->nextId, $id + 1);
        $now = new DateTimeImmutable('2026-07-19 12:00:00+00:00');
        $saved = new Note(
            $id,
            $note->userId,
            $note->categoryId,
            $note->title,
            $note->content,
            $note->createdAt ?? $now,
            $now,
        );
        $this->notes[$id] = $saved;

        return $saved;
    }

    public function delete(Note $note): void
    {
        if ($note->id !== null) {
            unset($this->notes[$note->id]);
        }
    }
}

final class NoteTestCategoryRepository implements CategoryRepositoryInterface
{
    /** @var array<int, Category> */
    private array $categories = [];

    /**
     * @param list<Category> $categories
     */
    public function __construct(array $categories)
    {
        foreach ($categories as $category) {
            $this->categories[$category->id] = $category;
        }
    }

    public function findById(int $id): ?Category
    {
        return $this->categories[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->categories);
    }
}

final class NoteTestLogger implements EventLoggerInterface
{
    public function info(string $message, array $context = []): void
    {
    }

    public function warning(string $message, array $context = []): void
    {
    }
}
