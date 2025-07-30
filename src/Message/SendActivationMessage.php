<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final class SendActivationMessage
{
    private string $from;
    private string $to;
    private string $subject;
    private string $template;
    private array $context;


    public function __construct($from, $to, $subject, $template, $context)
    {
        $this->from = $from;
        $this->to = $to;
        $this->subject = $subject;
        $this->template = $template;
        $this->context = $context;
    }

    /**
     * Get the value of from
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * Get the value of to
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * Get the value of subject
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Get the value of template
     */
    public function getTemplate(): string
    {
        return $this->template;
    }

    /**
     * Get the value of context
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
