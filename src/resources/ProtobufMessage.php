<?php
// @codingStandardsIgnoreFile

/**
 * This file should not be included in any auto loaded path, its purpose is only to document the public interface
 * of the allegro protobuf c extension.
 */
abstract class ProtobufMessage
{
    protected $values;

    const PB_TYPE_DOUBLE = 1;
    const PB_TYPE_FIXED32 = 2;
    const PB_TYPE_FIXED64 = 3;
    const PB_TYPE_FLOAT = 4;
    const PB_TYPE_INT = 5;
    const PB_TYPE_SIGNED_INT = 6;
    const PB_TYPE_STRING = 7;
    const PB_TYPE_BOOL = 8;

    /**
     * @return array
     */
    abstract public function getFields();

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getValue($id)
    {
        // Native code
    }

    /**
     * @param int $id
     * @param string $value
     */
    public function setValue($id, $value)
    {
        // Native code
    }

    /**
     * @param int $id
     * @param string $value
     */
    public function appendValue($id, $value) {
        // Native code
    }

    /**
     * @param int $id
     */
    public function clearValues($id) {
        // Native code
    }

    /**
     * @return int
     */
    public function getCount()
    {
        // Native code
    }

    /**
     * @param string $string
     *
     * @return bool success, If true is not returned the parse will be halted.
     */
    public function parseFromString($string)
    {
        // Native code
    }

    /**
     * @return string
     */
    public function serializeToString()
    {
        // Native code
    }
}
