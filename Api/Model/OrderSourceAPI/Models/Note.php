<?php
namespace Auctane\Api\Model\OrderSourceAPI\Models;

/**
 * This represents a note to the buyer, seller, or recipient
 */
class Note
{
    /** @var NoteType $type The type of note being sent */
    public NoteType $type;

    /** @var string $text The contents of the note */
    public string $text;

    /**
     * Note constructor.
     * @param array|null $data
     */
    public function __construct(?array $data = null)
    {
        if ($data) {
            $this->type = $data["type"] ?? null;
            $this->text = $data["text"] ?? null;
        }
    }
}
