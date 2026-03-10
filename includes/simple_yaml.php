<?php

declare(strict_types=1);

final class SimpleYamlParseException extends RuntimeException
{
    public function __construct(string $message, public readonly int $line_number)
    {
        parent::__construct($message);
    }
}

/**
 * Parse a restricted YAML subset used by content frontmatter.
 */
final class SimpleYamlParser
{
    /** @var array<int, array{raw:string, number:int}> */
    private array $lines = [];
    private int $index = 0;

    public function parse(string $yaml): mixed
    {
        $this->lines = [];
        $this->index = 0;

        $raw_lines = preg_split('/\R/', $yaml) ?: [];

        foreach ($raw_lines as $offset => $raw_line) {
            $this->lines[] = [
                'raw' => rtrim($raw_line, "\r\n"),
                'number' => $offset + 1,
            ];
        }

        $this->skip_empty_lines();

        if ($this->is_end()) {
            return [];
        }

        $result = $this->parse_block(0);
        $this->skip_empty_lines();

        if (!$this->is_end()) {
            $line = $this->current_line();
            throw new SimpleYamlParseException('Unexpected trailing content.', $line['number']);
        }

        return $result;
    }

    private function parse_block(int $indent): mixed
    {
        $this->skip_empty_lines();

        if ($this->is_end()) {
            return [];
        }

        $line = $this->current_line();
        $line_indent = $this->indent($line['raw']);

        if ($line_indent < $indent) {
            return [];
        }

        $trimmed = ltrim($line['raw']);

        if (str_starts_with($trimmed, '- ')) {
            return $this->parse_sequence($indent);
        }

        return $this->parse_mapping($indent);
    }

    /**
     * @return array<string, mixed>
     */
    private function parse_mapping(int $indent): array
    {
        $result = [];

        while (!$this->is_end()) {
            $this->skip_empty_lines();

            if ($this->is_end()) {
                break;
            }

            $line = $this->current_line();
            $raw = $line['raw'];
            $line_indent = $this->indent($raw);

            if ($line_indent < $indent) {
                break;
            }

            if ($line_indent > $indent) {
                throw new SimpleYamlParseException('Unexpected indentation in mapping.', $line['number']);
            }

            $trimmed = ltrim($raw);

            if (str_starts_with($trimmed, '- ')) {
                break;
            }

            if (!preg_match('/^([A-Za-z0-9_.-]+)\s*:\s*(.*)$/', $trimmed, $matches)) {
                throw new SimpleYamlParseException('Expected key: value format.', $line['number']);
            }

            $key = (string) $matches[1];
            $value_part = (string) $matches[2];
            $this->index++;

            if ($value_part === '') {
                if ($this->has_child_line($indent + 2)) {
                    $result[$key] = $this->parse_block($indent + 2);
                } else {
                    $result[$key] = null;
                }
                continue;
            }

            if (trim($value_part) === '|') {
                $result[$key] = $this->parse_literal_block($indent + 2);
                continue;
            }

            $result[$key] = $this->parse_scalar($value_part, $line['number']);
        }

        return $result;
    }

    /**
     * @return array<int, mixed>
     */
    private function parse_sequence(int $indent): array
    {
        $result = [];

        while (!$this->is_end()) {
            $this->skip_empty_lines();

            if ($this->is_end()) {
                break;
            }

            $line = $this->current_line();
            $raw = $line['raw'];
            $line_indent = $this->indent($raw);

            if ($line_indent < $indent) {
                break;
            }

            if ($line_indent > $indent) {
                throw new SimpleYamlParseException('Unexpected indentation in sequence.', $line['number']);
            }

            $trimmed = ltrim($raw);

            if (!str_starts_with($trimmed, '- ')) {
                break;
            }

            $item_part = substr($trimmed, 2);
            $this->index++;

            if ($item_part === '') {
                if ($this->has_child_line($indent + 2)) {
                    $result[] = $this->parse_block($indent + 2);
                } else {
                    $result[] = null;
                }
                continue;
            }

            if (preg_match('/^([A-Za-z0-9_.-]+)\s*:\s*(.*)$/', $item_part, $matches)) {
                $result[] = $this->parse_inline_mapping_item($indent, (string) $matches[1], (string) $matches[2], $line['number']);
                continue;
            }

            $result[] = $this->parse_scalar($item_part, $line['number']);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function parse_inline_mapping_item(int $sequence_indent, string $first_key, string $first_value_part, int $line_number): array
    {
        $item = [];
        $mapping_indent = $sequence_indent + 2;

        if ($first_value_part === '') {
            if ($this->has_child_line($mapping_indent + 2)) {
                $item[$first_key] = $this->parse_block($mapping_indent + 2);
            } else {
                $item[$first_key] = null;
            }
        } elseif (trim($first_value_part) === '|') {
            $item[$first_key] = $this->parse_literal_block($mapping_indent + 2);
        } else {
            $item[$first_key] = $this->parse_scalar($first_value_part, $line_number);
        }

        while (!$this->is_end()) {
            $this->skip_empty_lines();

            if ($this->is_end()) {
                break;
            }

            $line = $this->current_line();
            $raw = $line['raw'];
            $indent = $this->indent($raw);
            $trimmed = ltrim($raw);

            if ($indent < $mapping_indent) {
                break;
            }

            if ($indent === $sequence_indent && str_starts_with($trimmed, '- ')) {
                break;
            }

            if ($indent > $mapping_indent) {
                throw new SimpleYamlParseException('Unexpected indentation inside list item mapping.', $line['number']);
            }

            if (!preg_match('/^([A-Za-z0-9_.-]+)\s*:\s*(.*)$/', $trimmed, $matches)) {
                throw new SimpleYamlParseException('Expected key: value in list item.', $line['number']);
            }

            $key = (string) $matches[1];
            $value_part = (string) $matches[2];
            $this->index++;

            if ($value_part === '') {
                if ($this->has_child_line($mapping_indent + 2)) {
                    $item[$key] = $this->parse_block($mapping_indent + 2);
                } else {
                    $item[$key] = null;
                }
                continue;
            }

            if (trim($value_part) === '|') {
                $item[$key] = $this->parse_literal_block($mapping_indent + 2);
                continue;
            }

            $item[$key] = $this->parse_scalar($value_part, $line['number']);
        }

        return $item;
    }

    private function parse_literal_block(int $indent): string
    {
        $chunks = [];

        while (!$this->is_end()) {
            $line = $this->current_line();
            $raw = $line['raw'];

            if (trim($raw) === '') {
                $chunks[] = '';
                $this->index++;
                continue;
            }

            $line_indent = $this->indent($raw);

            if ($line_indent < $indent) {
                break;
            }

            $chunks[] = (string) substr($raw, $indent);
            $this->index++;
        }

        return rtrim(implode("\n", $chunks), "\n");
    }

    private function parse_scalar(string $value_part, int $line_number): mixed
    {
        $value = trim($value_part);

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            return $this->parse_inline_array($value, $line_number);
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        if (preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        $lower = strtolower($value);

        if ($lower === 'true') {
            return true;
        }

        if ($lower === 'false') {
            return false;
        }

        if ($lower === 'null') {
            return null;
        }

        return $value;
    }

    /**
     * @return array<int, mixed>
     */
    private function parse_inline_array(string $value, int $line_number): array
    {
        $inner = trim(substr($value, 1, -1));

        if ($inner === '') {
            return [];
        }

        $parts = [];
        $buffer = '';
        $in_single = false;
        $in_double = false;
        $length = strlen($inner);

        for ($index = 0; $index < $length; $index++) {
            $character = $inner[$index];

            if ($character === "'" && !$in_double) {
                $in_single = !$in_single;
                $buffer .= $character;
                continue;
            }

            if ($character === '"' && !$in_single) {
                $in_double = !$in_double;
                $buffer .= $character;
                continue;
            }

            if ($character === ',' && !$in_single && !$in_double) {
                $parts[] = trim($buffer);
                $buffer = '';
                continue;
            }

            $buffer .= $character;
        }

        if ($in_single || $in_double) {
            throw new SimpleYamlParseException('Unclosed quote in inline array.', $line_number);
        }

        if (trim($buffer) !== '') {
            $parts[] = trim($buffer);
        }

        $result = [];

        foreach ($parts as $part) {
            $result[] = $this->parse_scalar($part, $line_number);
        }

        return $result;
    }

    private function has_child_line(int $required_indent): bool
    {
        $cursor = $this->index;

        while ($cursor < count($this->lines)) {
            $line = $this->lines[$cursor];
            $raw = $line['raw'];

            if (trim($raw) === '') {
                $cursor++;
                continue;
            }

            return $this->indent($raw) >= $required_indent;
        }

        return false;
    }

    private function skip_empty_lines(): void
    {
        while (!$this->is_end()) {
            $raw = $this->current_line()['raw'];

            if (trim($raw) === '' || preg_match('/^\s*#/', $raw) === 1) {
                $this->index++;
                continue;
            }

            break;
        }
    }

    /**
     * @return array{raw:string, number:int}
     */
    private function current_line(): array
    {
        return $this->lines[$this->index];
    }

    private function is_end(): bool
    {
        return $this->index >= count($this->lines);
    }

    private function indent(string $line): int
    {
        return strlen($line) - strlen(ltrim($line, ' '));
    }
}
