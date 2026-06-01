<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\Course;
use App\Models\Lecturer;
use App\Models\Laboratorium;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan ada data prerequisite
        $courses = Course::all();
        $lecturers = Lecturer::all();
        $labs = Laboratorium::take(3)->get();

        if ($courses->isEmpty() || $labs->isEmpty()) {
            $this->command->info('Skipping ScheduleSeeder: Tidak ada data Course atau Laboratorium.');
            return;
        }

        // Buat jadwal dengan data yang tersedia
        $course = $courses->first();
        $lecturer = $lecturers->first(); // Bisa null jika tidak ada

        // Data jadwal sample yang disesuaikan dengan data yang tersedia
        $schedules = [
            [
                'course_id' => $course->id,
                'lecturer_id' => $lecturer?->id,
                'laboratorium_id' => $labs[0]->id,
                'kelompok' => 'A',
                'day' => 'Senin',
                'start_time' => '08:00',
                'end_time' => '09:40',
            ],
            [
                'course_id' => $course->id,
                'lecturer_id' => $lecturer?->id,
                'laboratorium_id' => $labs[0]->id,
                'kelompok' => 'B',
                'day' => 'Senin',
                'start_time' => '10:00',
                'end_time' => '11:40',
            ],
            [
                'course_id' => $course->id,
                'lecturer_id' => null, // Belum ditentukan
                'laboratorium_id' => $labs[1]->id,
                'kelompok' => null,
                'day' => 'Selasa',
                'start_time' => '09:00',
                'end_time' => '11:30',
            ],
            [
                'course_id' => $course->id,
                'lecturer_id' => $lecturer?->id,
                'laboratorium_id' => $labs[0]->id,
                'kelompok' => 'C',
                'day' => 'Rabu',
                'start_time' => '13:00',
                'end_time' => '14:40',
            ],
            [
                'course_id' => $course->id,
                'lecturer_id' => null,
                'laboratorium_id' => $labs[2]->id,
                'kelompok' => 'A',
                'day' => 'Kamis',
                'start_time' => '15:00',
                'end_time' => '15:50',
            ],
        ];

        foreach ($schedules as $scheduleData) {
            Schedule::updateOrCreate(
                [
                    'laboratorium_id' => $scheduleData['laboratorium_id'],
                    'day' => $scheduleData['day'],
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                ],
                $scheduleData
            );
        }

        $this->command->info('Sample schedules created successfully!');
    }
}
