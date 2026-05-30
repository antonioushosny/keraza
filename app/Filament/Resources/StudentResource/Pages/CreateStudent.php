<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    public ?int $selectedClassId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedClassId = isset($data['class_id']) ? intval($data['class_id']) : null;
        unset($data['class_id']);

        $phone = $data['parent_phone'] ?? null;
        $parentName = $data['parent_name'] ?? null;

        if ($phone) {
            // Check for duplicate student full_name under this parent phone
            $existingStudent = \App\Models\Student::where('full_name', $data['full_name'])
                ->whereHas('parent', function($q) use ($phone) {
                    $q->where('phone', $phone);
                })->first();

            if ($existingStudent) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'full_name' => 'هذا المخدوم مسجل بالفعل تحت رقم موبايل ولي الأمر هذا.',
                ]);
            }

            $parent = \App\Models\User::where('phone', $phone)->first();
            if (!$parent) {
                $parent = \App\Models\User::create([
                    'name' => $parentName ?: ('ولي أمر ' . $data['full_name']),
                    'phone' => $phone,
                    'password' => bcrypt('123456'),
                ]);
                $parent->assignRole('parent');
            } else {
                // Keep parent name updated if changed
                if ($parentName && $parent->name !== $parentName) {
                    $parent->update(['name' => $parentName]);
                }
            }

            $data['parent_id'] = $parent->id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $student = $this->record;
        
        $activeSeason = \App\Models\Season::active();
        if ($activeSeason && $this->selectedClassId) {
            \App\Models\StudentSeasonEnrollment::updateOrCreate([
                'student_id' => $student->id,
                'season_id' => $activeSeason->id,
            ], [
                'class_id' => $this->selectedClassId,
            ]);
        }
    }
}
