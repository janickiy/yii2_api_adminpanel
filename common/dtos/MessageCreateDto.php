<?php

declare(strict_types=1);

namespace common\dtos;

final class MessageCreateDto
{
    public string $subject;
    public string $email;
    public ?string $phone;
    public string $message;

    public function __construct(
        string $subject,
        string $email,
        ?string $phone,
        string $message,
    ) {
        $this->subject = $subject;
        $this->email = $email;
        $this->phone = $phone;
        $this->message = $message;
    }
}
