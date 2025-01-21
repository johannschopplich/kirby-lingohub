<?php

declare(strict_types=1);

namespace JohannSchopplich\Lingohub;

class Multipart
{
    private string $boundary;
    private array $data = [];

    public function __construct()
    {
        $this->boundary = '----' . uniqid('boundary', true);
    }

    public function addField(string $name, string $value): self
    {
        $this->data[] = [
            'type' => 'field',
            'name' => $name,
            'content' => $value
        ];
        return $this;
    }

    public function addFile(
        string $name,
        string $content,
        string $filename,
        string $contentType = 'application/json'
    ): self {
        $this->data[] = [
            'type' => 'file',
            'name' => $name,
            'content' => $content,
            'filename' => $filename,
            'contentType' => $contentType
        ];
        return $this;
    }

    public function getBoundary(): string
    {
        return $this->boundary;
    }

    public function getContentTypeHeader(): string
    {
        return 'multipart/form-data; boundary=' . $this->boundary;
    }

    public function build(): string
    {
        $output = '';

        foreach ($this->data as $part) {
            $output .= "--{$this->boundary}\r\n";

            if ($part['type'] === 'field') {
                $output .= "Content-Disposition: form-data; name=\"{$part['name']}\"\r\n\r\n";
                $output .= "{$part['content']}\r\n";
            } else {
                $output .= "Content-Disposition: form-data; name=\"{$part['name']}\"; filename=\"{$part['filename']}\"\r\n";
                $output .= "Content-Type: {$part['contentType']}\r\n\r\n";
                $output .= "{$part['content']}\r\n";
            }
        }

        $output .= "--{$this->boundary}--\r\n";
        return $output;
    }
}
