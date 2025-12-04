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
        try {
            return $this->amoClient->getAll("leads/{$leadId}/notes");
        } catch (\Exception $e) {
            error_log("Ошибка получения примечаний для сделки {$leadId}: " . $e->getMessage());
            return [];
        }
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

        // Используем правильный эндпоинт для создания примечаний
        $response = $this->amoClient->post('leads/notes', $preparedNotes);
        
        // Проверяем, что примечания были созданы
        if (isset($response['_embedded']['notes'])) {
            return count($response['_embedded']['notes']);
        }
        
        return 0;
    }

    private function prepareNoteForCopy(array $note, int $targetLeadId)
    {
        // Всегда создаем примечание, даже если не все поля идеальны
        $newNote = [
            "entity_id" => $targetLeadId,
            "note_type" => $note['note_type']
        ];
        
        // Создаем params с текстом
        $newNote['params'] = [];
        
        // Копируем все params из исходного примечания
        if (isset($note['params']) && is_array($note['params'])) {
            $newNote['params'] = $note['params'];
        }
        
        // Если нет текста в params, добавляем заглушку
        if (!isset($newNote['params']['text']) || empty($newNote['params']['text'])) {
            $newNote['params']['text'] = 'Примечание из сделки-донора';
        }
        
        // Добавляем дополнительные поля если есть
        if (isset($note['created_by'])) {
            $newNote['created_by'] = $note['created_by'];
        }
        
        return $newNote;
    }
}