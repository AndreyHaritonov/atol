<?php

namespace Andrey\Atol;

class Memcached
{
    public $debugMode = false;

    private const ANSWER_END = 'END';
    private const ANSWER_STORED = 'STORED';

    private $stream;

    /**
     * Подключиться
     */
    public function connect(string $address): void
    {
        $stream = @stream_socket_client($address, $errno, $errstr);
        if (!$stream) {
            throw new MemcachedException("{$errstr} ({$errno})");
        }
        $this->stream = $stream;
    }

    /**
     * Отключиться
     */
    public function close(): void
    {
        @fclose($this->stream);
    }

    /**
     * Получить значение для ключа
     */
    public function get(string $key): string
    {
        $this->sendCommand("get {$key}");
        $answer = $this->readToEnd();
        if (!$answer) {
            return '';
        }
        if (count($answer) < 2 || !preg_match("/^VALUE /", $answer[0])) {
            throw new MemcachedException("Unexpected answer (" . implode("\\r\\n", $answer) . ")");
        }
        return $answer[1];
    }

    /**
     * Установить значение для ключа
     */
    public function set(string $key, string $value, int $expires = 0, int $flags = 0): void
    {
        $length = strlen($value);
        $this->sendCommand("set {$key} {$flags} {$expires} {$length}");
        $this->sendCommand($value);
        $answer = $this->read();
        if ($answer !== self::ANSWER_STORED) {
            throw new MemcachedException($answer);
        }
    }

    /**
     * Удалить значение по ключу
     */
    public function delete(string $key): void
    {
        $this->sendCommand("delete {$key}");
        $this->read();
    }

    /**
     * Отправить команду и добавить \r\n
     */
    private function sendCommand(string $command): void
    {
        if (!$this->stream) {
            throw new MemcachedException("Not connected");
        }
        $wrapped = "{$command}\r\n";
        $res = @fwrite($this->stream, $wrapped);
        if (strlen($wrapped) !== $res) {
            throw new MemcachedException("Write error ({$command})");
        }
        if ($this->debugMode) {
            echo "> {$command}\n";
        }
    }

    /**
     * Читаем одну строку из ответа
     */
    private function read(): string
    {
        if (!$this->stream) {
            throw new MemcachedException("Not connected");
        }
        $row = fgets($this->stream);
        if ($row === false) {
            throw new MemcachedException("Read error");
        }
        $row = rtrim($row);
        if ($this->debugMode) {
            echo "< {$row}\n";
        }
        return $row;
    }

    /**
     * Читаем строки до END или пока их количество не достигнет $limit
     */
    private function readToEnd(int $limit = 1000): array
    {
        $rows = [];
        while (count($rows) < $limit) {
            $row = $this->read();
            if ($row === self::ANSWER_END) {
                break;
            }
            $rows[] = $row;
        }
        return $rows;
    }
}
