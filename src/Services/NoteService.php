<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\NoteRepository;

class NoteService
{
    public function __construct(
        private readonly NoteRepository $noteRepository
    ) {}
    
    public function getLeadNotes(int $leadId): array
    {
        try {
            return $this->noteRepository->findByEntityId($leadId, 'leads');
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
            if ($newNote !== null) {
                $preparedNotes[] = $newNote;
            }
        }

        if (empty($preparedNotes)) {
            return 0;
        }

        // Используем пакетное создание примечаний
        return $this->noteRepository->batchCreateForEntity($targetLeadId, 'leads', $preparedNotes);
    }
    
    private function prepareNoteForCopy(array $note, int $targetLeadId): ?array
    {
        if (!isset($note['note_type'])) {
            return null;
        }
        
        $newNote = [
            "entity_id" => $targetLeadId,
            "note_type" => $note['note_type'],
            "params" => []
        ];
        
        // Копируем params из исходного примечания
        if (isset($note['params']) && is_array($note['params'])) {
            $newNote['params'] = $note['params'];
        }
        
        // Обеспечиваем наличие текста
        if (!isset($newNote['params']['text']) || empty($newNote['params']['text'])) {
            $newNote['params']['text'] = 'Примечание из сделки-донора (ID: ' . $targetLeadId . ')';
        }
        
        // Добавляем дополнительные поля если есть
        if (isset($note['created_by'])) {
            $newNote['created_by'] = $note['created_by'];
        }
        
        if (isset($note['created_at'])) {
            $newNote['created_at'] = $note['created_at'];
        }
        
        return $newNote;
    }
}