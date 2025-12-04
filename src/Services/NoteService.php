<?php
namespace App\Services;

use App\Client\AmoCrmV4Client;

/**
 * Сервис для работы с примечаниями
 */
class NoteService
{
    private AmoCrmV4Client $amoClient;

    public function __construct(AmoCrmV4Client $amoClient)
    {
        $this->amoClient = $amoClient;
    }

    public function getLeadNotes(int $leadId): array
    {
        return $this->amoClient->getAll("leads/{$leadId}/notes");
    }

    public function copyNotes(int $sourceLeadId, int $targetLeadId): int
    {
        $notes = $this->getLeadNotes($sourceLeadId);
        
        if (empty($notes)) {
            return 0;
        }

        $preparedNotes = [];
        foreach ($notes as $note) {
            $newNote = $this->prepareNoteForCopy($note, $targetLeadId);
            if ($newNote) {
                $preparedNotes[] = $newNote;
            }
        }

        if (empty($preparedNotes)) {
            return 0;
        }

        $this->amoClient->post('leads/notes', $preparedNotes);
        return count($preparedNotes);
    }

    private function prepareNoteForCopy(array $note, int $targetLeadId): ?array
    {
        $newNote = [
            "entity_id" => $targetLeadId,
            "note_type" => $note['note_type'],
            "params" => $note['params'] ?? [],
            "created_by" => $note['created_by'] ?? 0
        ];

        if (!empty($note['text'])) {
            $newNote['text'] = $note['text'];
        } elseif (!empty($note['params']['text'])) {
            $newNote['params']['text'] = $note['params']['text'];
        }

        if (empty($newNote['text']) && empty($newNote['params'])) {
            return null;
        }

        return $newNote;
    }
}