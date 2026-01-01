<?php

namespace Database\Seeders;

use App\Models\GradeScale;
use App\Models\GradingSystem;
use Illuminate\Database\Seeder;

class GradingSystemSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMalawiPrimarySystem();
        $this->seedMalawiJCESystem();
        $this->seedMalawiMSCESystem();
        $this->seedInternationalSystems();
    }

    protected function seedMalawiPrimarySystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'malawi-primary'],
            [
                'name' => 'Malawi Primary School (PSLCE)',
                'description' => 'Grading system for Malawi Primary Schools (Standards 1-8) including PSLCE',
                'type' => GradingSystem::TYPE_PRIMARY,
                'scale_type' => GradingSystem::SCALE_PERCENTAGE,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 50,
                'min_subjects_to_pass' => 4,
                'priority_subjects' => ['ENG', 'MAT'],
                'certification_rules' => [
                    'min_subjects' => 4,
                    'priority_subjects_required' => ['ENG', 'MAT'],
                    'min_priority_subjects' => 2,
                ],
                'progression_rules' => [
                    'require_priority_pass' => true,
                    'min_average' => 50,
                ],
                'settings' => [
                    'priority_pass_mark' => 50,
                    'require_priority_pass' => true,
                    'continuous_assessment_weight' => 30,
                    'exam_weight' => 70,
                ],
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $this->createPrimaryGradeScales($system);
    }

    protected function createPrimaryGradeScales(GradingSystem $system): void
    {
        $scales = [
            ['grade' => 'A', 'grade_label' => 'Excellent', 'min_score' => 80, 'max_score' => 100, 'points' => 1, 'remark' => 'Outstanding Performance', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => 'B', 'grade_label' => 'Very Good', 'min_score' => 70, 'max_score' => 79.99, 'points' => 2, 'remark' => 'Above Average', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => 'C', 'grade_label' => 'Good', 'min_score' => 60, 'max_score' => 69.99, 'points' => 3, 'remark' => 'Average Performance', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => 'D', 'grade_label' => 'Satisfactory', 'min_score' => 50, 'max_score' => 59.99, 'points' => 4, 'remark' => 'Below Average', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => 'F', 'grade_label' => 'Fail', 'min_score' => 0, 'max_score' => 49.99, 'points' => 9, 'remark' => 'Needs Improvement', 'is_passing' => false, 'sort_order' => 5],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedMalawiJCESystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'malawi-jce'],
            [
                'name' => 'Malawi Junior Certificate of Education (JCE)',
                'description' => 'Grading system for Malawi Secondary Schools Forms 1-2 (JCE)',
                'type' => GradingSystem::TYPE_SECONDARY_JCE,
                'scale_type' => GradingSystem::SCALE_PERCENTAGE,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 50,
                'min_subjects_to_pass' => 6,
                'priority_subjects' => ['ENG'],
                'certification_rules' => [
                    'min_subjects' => 6,
                    'priority_subjects_required' => ['ENG'],
                    'min_priority_subjects' => 1,
                ],
                'progression_rules' => [
                    'require_priority_pass' => true,
                    'min_average' => 50,
                ],
                'settings' => [
                    'priority_pass_mark' => 50,
                    'require_priority_pass' => true,
                ],
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $this->createJCEGradeScales($system);
    }

    protected function createJCEGradeScales(GradingSystem $system): void
    {
        $scales = [
            ['grade' => 'A', 'grade_label' => 'Distinction', 'min_score' => 75, 'max_score' => 100, 'points' => 1, 'remark' => 'Distinction', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => 'B', 'grade_label' => 'Credit', 'min_score' => 65, 'max_score' => 74.99, 'points' => 2, 'remark' => 'Credit', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => 'C', 'grade_label' => 'Credit', 'min_score' => 55, 'max_score' => 64.99, 'points' => 3, 'remark' => 'Credit', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => 'D', 'grade_label' => 'Pass', 'min_score' => 50, 'max_score' => 54.99, 'points' => 4, 'remark' => 'Pass', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => 'F', 'grade_label' => 'Fail', 'min_score' => 0, 'max_score' => 49.99, 'points' => 9, 'remark' => 'Fail', 'is_passing' => false, 'sort_order' => 5],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedMalawiMSCESystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'malawi-msce'],
            [
                'name' => 'Malawi School Certificate of Education (MSCE)',
                'description' => 'Grading system for Malawi Secondary Schools Forms 3-4 (MSCE)',
                'type' => GradingSystem::TYPE_SECONDARY_MSCE,
                'scale_type' => GradingSystem::SCALE_NUMERIC,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 50,
                'min_subjects_to_pass' => 6,
                'priority_subjects' => ['ENG'],
                'certification_rules' => [
                    'min_subjects' => 6,
                    'priority_subjects_required' => ['ENG'],
                    'min_priority_subjects' => 1,
                    'max_grade_for_pass' => 8,
                ],
                'progression_rules' => [
                    'require_priority_pass' => true,
                    'min_average' => 50,
                ],
                'settings' => [
                    'priority_pass_mark' => 50,
                    'require_priority_pass' => true,
                    'use_numeric_grades' => true,
                ],
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $this->createMSCEGradeScales($system);
    }

    protected function createMSCEGradeScales(GradingSystem $system): void
    {
        $scales = [
            ['grade' => '1', 'grade_label' => 'A+', 'min_score' => 90, 'max_score' => 100, 'points' => 1, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => '2', 'grade_label' => 'A', 'min_score' => 80, 'max_score' => 89.99, 'points' => 2, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => '3', 'grade_label' => 'B+', 'min_score' => 70, 'max_score' => 79.99, 'points' => 3, 'remark' => 'Very Good', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => '4', 'grade_label' => 'B', 'min_score' => 65, 'max_score' => 69.99, 'points' => 4, 'remark' => 'Very Good', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => '5', 'grade_label' => 'C+', 'min_score' => 60, 'max_score' => 64.99, 'points' => 5, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 5],
            ['grade' => '6', 'grade_label' => 'C', 'min_score' => 55, 'max_score' => 59.99, 'points' => 6, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 6],
            ['grade' => '7', 'grade_label' => 'D+', 'min_score' => 50, 'max_score' => 54.99, 'points' => 7, 'remark' => 'Pass', 'is_passing' => true, 'sort_order' => 7],
            ['grade' => '8', 'grade_label' => 'D', 'min_score' => 40, 'max_score' => 49.99, 'points' => 8, 'remark' => 'Pass', 'is_passing' => true, 'sort_order' => 8],
            ['grade' => '9', 'grade_label' => 'F', 'min_score' => 0, 'max_score' => 39.99, 'points' => 9, 'remark' => 'Fail', 'is_passing' => false, 'sort_order' => 9],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedInternationalSystems(): void
    {
        $this->seedUSGradingSystem();
        $this->seedUKGCSESystem();
        $this->seedUKALevelSystem();
        $this->seedIBSystem();
        $this->seedECTSSystem();
        $this->seedGermanSystem();
        $this->seedFrenchSystem();
        $this->seedIndianSystem();
        $this->seedAustralianSystem();
        $this->seedCanadianSystem();
        $this->seedJapaneseSystem();
        $this->seedSwissSystem();
    }

    protected function seedUSGradingSystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'us-standard'],
            [
                'name' => 'US Standard (A-F, GPA 0-4.0)',
                'description' => 'United States standard grading system with GPA',
                'type' => GradingSystem::TYPE_INTERNATIONAL,
                'scale_type' => GradingSystem::SCALE_GPA,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 60,
                'min_subjects_to_pass' => 5,
                'settings' => ['gpa_scale' => 4.0],
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $scales = [
            ['grade' => 'A+', 'grade_label' => 'Excellent', 'min_score' => 97, 'max_score' => 100, 'gpa_points' => 4.0, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => 'A', 'grade_label' => 'Excellent', 'min_score' => 93, 'max_score' => 96.99, 'gpa_points' => 4.0, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => 'A-', 'grade_label' => 'Excellent', 'min_score' => 90, 'max_score' => 92.99, 'gpa_points' => 3.7, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => 'B+', 'grade_label' => 'Good', 'min_score' => 87, 'max_score' => 89.99, 'gpa_points' => 3.3, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => 'B', 'grade_label' => 'Good', 'min_score' => 83, 'max_score' => 86.99, 'gpa_points' => 3.0, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 5],
            ['grade' => 'B-', 'grade_label' => 'Good', 'min_score' => 80, 'max_score' => 82.99, 'gpa_points' => 2.7, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 6],
            ['grade' => 'C+', 'grade_label' => 'Satisfactory', 'min_score' => 77, 'max_score' => 79.99, 'gpa_points' => 2.3, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 7],
            ['grade' => 'C', 'grade_label' => 'Satisfactory', 'min_score' => 73, 'max_score' => 76.99, 'gpa_points' => 2.0, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 8],
            ['grade' => 'C-', 'grade_label' => 'Satisfactory', 'min_score' => 70, 'max_score' => 72.99, 'gpa_points' => 1.7, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 9],
            ['grade' => 'D+', 'grade_label' => 'Poor', 'min_score' => 67, 'max_score' => 69.99, 'gpa_points' => 1.3, 'remark' => 'Below Average', 'is_passing' => true, 'sort_order' => 10],
            ['grade' => 'D', 'grade_label' => 'Poor', 'min_score' => 63, 'max_score' => 66.99, 'gpa_points' => 1.0, 'remark' => 'Below Average', 'is_passing' => true, 'sort_order' => 11],
            ['grade' => 'D-', 'grade_label' => 'Poor', 'min_score' => 60, 'max_score' => 62.99, 'gpa_points' => 0.7, 'remark' => 'Below Average', 'is_passing' => true, 'sort_order' => 12],
            ['grade' => 'F', 'grade_label' => 'Fail', 'min_score' => 0, 'max_score' => 59.99, 'gpa_points' => 0.0, 'remark' => 'Fail', 'is_passing' => false, 'sort_order' => 13],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedUKGCSESystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'uk-gcse'],
            [
                'name' => 'UK GCSE (9-1)',
                'description' => 'United Kingdom GCSE grading system (9-1 scale)',
                'type' => GradingSystem::TYPE_INTERNATIONAL,
                'scale_type' => GradingSystem::SCALE_NUMERIC,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 40,
                'min_subjects_to_pass' => 5,
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $scales = [
            ['grade' => '9', 'grade_label' => 'A**', 'min_score' => 90, 'max_score' => 100, 'points' => 9, 'remark' => 'Exceptional', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => '8', 'grade_label' => 'A*', 'min_score' => 80, 'max_score' => 89.99, 'points' => 8, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => '7', 'grade_label' => 'A', 'min_score' => 70, 'max_score' => 79.99, 'points' => 7, 'remark' => 'Very Good', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => '6', 'grade_label' => 'B', 'min_score' => 60, 'max_score' => 69.99, 'points' => 6, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => '5', 'grade_label' => 'C+', 'min_score' => 50, 'max_score' => 59.99, 'points' => 5, 'remark' => 'Strong Pass', 'is_passing' => true, 'sort_order' => 5],
            ['grade' => '4', 'grade_label' => 'C', 'min_score' => 40, 'max_score' => 49.99, 'points' => 4, 'remark' => 'Standard Pass', 'is_passing' => true, 'sort_order' => 6],
            ['grade' => '3', 'grade_label' => 'D', 'min_score' => 30, 'max_score' => 39.99, 'points' => 3, 'remark' => 'Below Pass', 'is_passing' => false, 'sort_order' => 7],
            ['grade' => '2', 'grade_label' => 'E', 'min_score' => 20, 'max_score' => 29.99, 'points' => 2, 'remark' => 'Limited', 'is_passing' => false, 'sort_order' => 8],
            ['grade' => '1', 'grade_label' => 'F/G', 'min_score' => 0, 'max_score' => 19.99, 'points' => 1, 'remark' => 'Very Limited', 'is_passing' => false, 'sort_order' => 9],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedUKALevelSystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'uk-alevel'],
            [
                'name' => 'UK A-Levels (A*-E)',
                'description' => 'United Kingdom A-Level grading system',
                'type' => GradingSystem::TYPE_INTERNATIONAL,
                'scale_type' => GradingSystem::SCALE_LETTER,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 40,
                'min_subjects_to_pass' => 3,
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $scales = [
            ['grade' => 'A*', 'grade_label' => 'Exceptional', 'min_score' => 90, 'max_score' => 100, 'points' => 56, 'remark' => 'Exceptional', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => 'A', 'grade_label' => 'Excellent', 'min_score' => 80, 'max_score' => 89.99, 'points' => 48, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => 'B', 'grade_label' => 'Good', 'min_score' => 70, 'max_score' => 79.99, 'points' => 40, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => 'C', 'grade_label' => 'Satisfactory', 'min_score' => 60, 'max_score' => 69.99, 'points' => 32, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => 'D', 'grade_label' => 'Pass', 'min_score' => 50, 'max_score' => 59.99, 'points' => 24, 'remark' => 'Pass', 'is_passing' => true, 'sort_order' => 5],
            ['grade' => 'E', 'grade_label' => 'Pass', 'min_score' => 40, 'max_score' => 49.99, 'points' => 16, 'remark' => 'Minimum Pass', 'is_passing' => true, 'sort_order' => 6],
            ['grade' => 'U', 'grade_label' => 'Ungraded', 'min_score' => 0, 'max_score' => 39.99, 'points' => 0, 'remark' => 'Ungraded', 'is_passing' => false, 'sort_order' => 7],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedIBSystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'ib-diploma'],
            [
                'name' => 'International Baccalaureate (IB)',
                'description' => 'IB Diploma Programme grading (1-7 scale)',
                'type' => GradingSystem::TYPE_INTERNATIONAL,
                'scale_type' => GradingSystem::SCALE_POINTS,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 40,
                'min_subjects_to_pass' => 6,
                'settings' => [
                    'max_points' => 45,
                    'core_components' => ['TOK', 'EE'],
                    'core_max_points' => 3,
                ],
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $scales = [
            ['grade' => '7', 'grade_label' => 'Excellent', 'min_score' => 85, 'max_score' => 100, 'points' => 7, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => '6', 'grade_label' => 'Very Good', 'min_score' => 70, 'max_score' => 84.99, 'points' => 6, 'remark' => 'Very Good', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => '5', 'grade_label' => 'Good', 'min_score' => 55, 'max_score' => 69.99, 'points' => 5, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => '4', 'grade_label' => 'Satisfactory', 'min_score' => 40, 'max_score' => 54.99, 'points' => 4, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => '3', 'grade_label' => 'Mediocre', 'min_score' => 30, 'max_score' => 39.99, 'points' => 3, 'remark' => 'Mediocre', 'is_passing' => false, 'sort_order' => 5],
            ['grade' => '2', 'grade_label' => 'Poor', 'min_score' => 20, 'max_score' => 29.99, 'points' => 2, 'remark' => 'Poor', 'is_passing' => false, 'sort_order' => 6],
            ['grade' => '1', 'grade_label' => 'Very Poor', 'min_score' => 0, 'max_score' => 19.99, 'points' => 1, 'remark' => 'Very Poor', 'is_passing' => false, 'sort_order' => 7],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedECTSSystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'ects-european'],
            [
                'name' => 'European ECTS (A-F)',
                'description' => 'European Credit Transfer System grading',
                'type' => GradingSystem::TYPE_INTERNATIONAL,
                'scale_type' => GradingSystem::SCALE_LETTER,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 50,
                'min_subjects_to_pass' => 5,
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $scales = [
            ['grade' => 'A', 'grade_label' => 'Excellent', 'min_score' => 90, 'max_score' => 100, 'remark' => 'Outstanding (Top 10%)', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => 'B', 'grade_label' => 'Very Good', 'min_score' => 80, 'max_score' => 89.99, 'remark' => 'Above Average (Next 25%)', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => 'C', 'grade_label' => 'Good', 'min_score' => 70, 'max_score' => 79.99, 'remark' => 'Average (Next 30%)', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => 'D', 'grade_label' => 'Satisfactory', 'min_score' => 60, 'max_score' => 69.99, 'remark' => 'Below Average (Next 25%)', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => 'E', 'grade_label' => 'Sufficient', 'min_score' => 50, 'max_score' => 59.99, 'remark' => 'Minimum Pass (Bottom 10%)', 'is_passing' => true, 'sort_order' => 5],
            ['grade' => 'F', 'grade_label' => 'Fail', 'min_score' => 0, 'max_score' => 49.99, 'remark' => 'Fail', 'is_passing' => false, 'sort_order' => 6],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedGermanSystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'german-standard'],
            [
                'name' => 'German Grading (1.0-6.0)',
                'description' => 'German standard grading system (1.0 best, 6.0 worst)',
                'type' => GradingSystem::TYPE_INTERNATIONAL,
                'scale_type' => GradingSystem::SCALE_NUMERIC,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 50,
                'min_subjects_to_pass' => 5,
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $scales = [
            ['grade' => '1.0', 'grade_label' => 'Sehr Gut', 'min_score' => 95, 'max_score' => 100, 'gpa_points' => 1.0, 'remark' => 'Very Good', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => '1.3', 'grade_label' => 'Sehr Gut', 'min_score' => 90, 'max_score' => 94.99, 'gpa_points' => 1.3, 'remark' => 'Very Good', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => '1.7', 'grade_label' => 'Gut', 'min_score' => 85, 'max_score' => 89.99, 'gpa_points' => 1.7, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => '2.0', 'grade_label' => 'Gut', 'min_score' => 80, 'max_score' => 84.99, 'gpa_points' => 2.0, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => '2.3', 'grade_label' => 'Gut', 'min_score' => 75, 'max_score' => 79.99, 'gpa_points' => 2.3, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 5],
            ['grade' => '2.7', 'grade_label' => 'Befriedigend', 'min_score' => 70, 'max_score' => 74.99, 'gpa_points' => 2.7, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 6],
            ['grade' => '3.0', 'grade_label' => 'Befriedigend', 'min_score' => 65, 'max_score' => 69.99, 'gpa_points' => 3.0, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 7],
            ['grade' => '3.3', 'grade_label' => 'Befriedigend', 'min_score' => 60, 'max_score' => 64.99, 'gpa_points' => 3.3, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 8],
            ['grade' => '3.7', 'grade_label' => 'Ausreichend', 'min_score' => 55, 'max_score' => 59.99, 'gpa_points' => 3.7, 'remark' => 'Sufficient', 'is_passing' => true, 'sort_order' => 9],
            ['grade' => '4.0', 'grade_label' => 'Ausreichend', 'min_score' => 50, 'max_score' => 54.99, 'gpa_points' => 4.0, 'remark' => 'Sufficient', 'is_passing' => true, 'sort_order' => 10],
            ['grade' => '5.0', 'grade_label' => 'Mangelhaft', 'min_score' => 25, 'max_score' => 49.99, 'gpa_points' => 5.0, 'remark' => 'Poor', 'is_passing' => false, 'sort_order' => 11],
            ['grade' => '6.0', 'grade_label' => 'Ungenügend', 'min_score' => 0, 'max_score' => 24.99, 'gpa_points' => 6.0, 'remark' => 'Very Poor', 'is_passing' => false, 'sort_order' => 12],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedFrenchSystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'french-standard'],
            [
                'name' => 'French Grading (0-20)',
                'description' => 'French standard grading system (0-20 scale)',
                'type' => GradingSystem::TYPE_INTERNATIONAL,
                'scale_type' => GradingSystem::SCALE_NUMERIC,
                'min_score' => 0,
                'max_score' => 20,
                'pass_mark' => 10,
                'min_subjects_to_pass' => 5,
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $scales = [
            ['grade' => '20', 'grade_label' => 'Parfait', 'min_score' => 20, 'max_score' => 20, 'remark' => 'Perfect', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => '18-19', 'grade_label' => 'Excellent', 'min_score' => 18, 'max_score' => 19.99, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => '16-17', 'grade_label' => 'Très Bien', 'min_score' => 16, 'max_score' => 17.99, 'remark' => 'Very Good', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => '14-15', 'grade_label' => 'Bien', 'min_score' => 14, 'max_score' => 15.99, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => '12-13', 'grade_label' => 'Assez Bien', 'min_score' => 12, 'max_score' => 13.99, 'remark' => 'Fairly Good', 'is_passing' => true, 'sort_order' => 5],
            ['grade' => '10-11', 'grade_label' => 'Passable', 'min_score' => 10, 'max_score' => 11.99, 'remark' => 'Pass', 'is_passing' => true, 'sort_order' => 6],
            ['grade' => '0-9', 'grade_label' => 'Insuffisant', 'min_score' => 0, 'max_score' => 9.99, 'remark' => 'Fail', 'is_passing' => false, 'sort_order' => 7],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedIndianSystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'indian-cbse'],
            [
                'name' => 'Indian CBSE (0-100%)',
                'description' => 'Indian CBSE grading system',
                'type' => GradingSystem::TYPE_INTERNATIONAL,
                'scale_type' => GradingSystem::SCALE_PERCENTAGE,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 33,
                'min_subjects_to_pass' => 5,
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $scales = [
            ['grade' => 'A1', 'grade_label' => 'Outstanding', 'min_score' => 91, 'max_score' => 100, 'gpa_points' => 10.0, 'remark' => 'Outstanding', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => 'A2', 'grade_label' => 'Excellent', 'min_score' => 81, 'max_score' => 90.99, 'gpa_points' => 9.0, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => 'B1', 'grade_label' => 'Very Good', 'min_score' => 71, 'max_score' => 80.99, 'gpa_points' => 8.0, 'remark' => 'Very Good', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => 'B2', 'grade_label' => 'Good', 'min_score' => 61, 'max_score' => 70.99, 'gpa_points' => 7.0, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => 'C1', 'grade_label' => 'Above Average', 'min_score' => 51, 'max_score' => 60.99, 'gpa_points' => 6.0, 'remark' => 'Above Average', 'is_passing' => true, 'sort_order' => 5],
            ['grade' => 'C2', 'grade_label' => 'Average', 'min_score' => 41, 'max_score' => 50.99, 'gpa_points' => 5.0, 'remark' => 'Average', 'is_passing' => true, 'sort_order' => 6],
            ['grade' => 'D', 'grade_label' => 'Below Average', 'min_score' => 33, 'max_score' => 40.99, 'gpa_points' => 4.0, 'remark' => 'Below Average', 'is_passing' => true, 'sort_order' => 7],
            ['grade' => 'E', 'grade_label' => 'Fail', 'min_score' => 0, 'max_score' => 32.99, 'gpa_points' => 0.0, 'remark' => 'Needs Improvement', 'is_passing' => false, 'sort_order' => 8],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedAustralianSystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'australian-standard'],
            [
                'name' => 'Australian Grading (HD-F)',
                'description' => 'Australian standard grading system',
                'type' => GradingSystem::TYPE_INTERNATIONAL,
                'scale_type' => GradingSystem::SCALE_LETTER,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 50,
                'min_subjects_to_pass' => 5,
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $scales = [
            ['grade' => 'HD', 'grade_label' => 'High Distinction', 'min_score' => 85, 'max_score' => 100, 'gpa_points' => 7.0, 'remark' => 'High Distinction', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => 'D', 'grade_label' => 'Distinction', 'min_score' => 75, 'max_score' => 84.99, 'gpa_points' => 6.0, 'remark' => 'Distinction', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => 'C', 'grade_label' => 'Credit', 'min_score' => 65, 'max_score' => 74.99, 'gpa_points' => 5.0, 'remark' => 'Credit', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => 'P', 'grade_label' => 'Pass', 'min_score' => 50, 'max_score' => 64.99, 'gpa_points' => 4.0, 'remark' => 'Pass', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => 'F', 'grade_label' => 'Fail', 'min_score' => 0, 'max_score' => 49.99, 'gpa_points' => 0.0, 'remark' => 'Fail', 'is_passing' => false, 'sort_order' => 5],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedCanadianSystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'canadian-standard'],
            [
                'name' => 'Canadian Grading (A-F)',
                'description' => 'Canadian standard grading system',
                'type' => GradingSystem::TYPE_INTERNATIONAL,
                'scale_type' => GradingSystem::SCALE_LETTER,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 50,
                'min_subjects_to_pass' => 5,
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $scales = [
            ['grade' => 'A+', 'grade_label' => 'Excellent', 'min_score' => 90, 'max_score' => 100, 'gpa_points' => 4.0, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => 'A', 'grade_label' => 'Excellent', 'min_score' => 85, 'max_score' => 89.99, 'gpa_points' => 4.0, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => 'A-', 'grade_label' => 'Excellent', 'min_score' => 80, 'max_score' => 84.99, 'gpa_points' => 3.7, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => 'B+', 'grade_label' => 'Good', 'min_score' => 77, 'max_score' => 79.99, 'gpa_points' => 3.3, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => 'B', 'grade_label' => 'Good', 'min_score' => 73, 'max_score' => 76.99, 'gpa_points' => 3.0, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 5],
            ['grade' => 'B-', 'grade_label' => 'Good', 'min_score' => 70, 'max_score' => 72.99, 'gpa_points' => 2.7, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 6],
            ['grade' => 'C+', 'grade_label' => 'Satisfactory', 'min_score' => 67, 'max_score' => 69.99, 'gpa_points' => 2.3, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 7],
            ['grade' => 'C', 'grade_label' => 'Satisfactory', 'min_score' => 63, 'max_score' => 66.99, 'gpa_points' => 2.0, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 8],
            ['grade' => 'C-', 'grade_label' => 'Satisfactory', 'min_score' => 60, 'max_score' => 62.99, 'gpa_points' => 1.7, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 9],
            ['grade' => 'D+', 'grade_label' => 'Marginal', 'min_score' => 57, 'max_score' => 59.99, 'gpa_points' => 1.3, 'remark' => 'Marginal Pass', 'is_passing' => true, 'sort_order' => 10],
            ['grade' => 'D', 'grade_label' => 'Marginal', 'min_score' => 53, 'max_score' => 56.99, 'gpa_points' => 1.0, 'remark' => 'Marginal Pass', 'is_passing' => true, 'sort_order' => 11],
            ['grade' => 'D-', 'grade_label' => 'Marginal', 'min_score' => 50, 'max_score' => 52.99, 'gpa_points' => 0.7, 'remark' => 'Marginal Pass', 'is_passing' => true, 'sort_order' => 12],
            ['grade' => 'F', 'grade_label' => 'Fail', 'min_score' => 0, 'max_score' => 49.99, 'gpa_points' => 0.0, 'remark' => 'Fail', 'is_passing' => false, 'sort_order' => 13],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedJapaneseSystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'japanese-standard'],
            [
                'name' => 'Japanese Grading (1-5)',
                'description' => 'Japanese standard grading system (5 best, 1 worst)',
                'type' => GradingSystem::TYPE_INTERNATIONAL,
                'scale_type' => GradingSystem::SCALE_NUMERIC,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 60,
                'min_subjects_to_pass' => 5,
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $scales = [
            ['grade' => '5', 'grade_label' => 'Shū (秀)', 'min_score' => 90, 'max_score' => 100, 'points' => 5, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => '4', 'grade_label' => 'Yū (優)', 'min_score' => 80, 'max_score' => 89.99, 'points' => 4, 'remark' => 'Very Good', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => '3', 'grade_label' => 'Ryō (良)', 'min_score' => 70, 'max_score' => 79.99, 'points' => 3, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => '2', 'grade_label' => 'Ka (可)', 'min_score' => 60, 'max_score' => 69.99, 'points' => 2, 'remark' => 'Pass', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => '1', 'grade_label' => 'Fuka (不可)', 'min_score' => 0, 'max_score' => 59.99, 'points' => 1, 'remark' => 'Fail', 'is_passing' => false, 'sort_order' => 5],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }

    protected function seedSwissSystem(): void
    {
        $system = GradingSystem::updateOrCreate(
            ['code' => 'swiss-standard'],
            [
                'name' => 'Swiss Grading (1-6)',
                'description' => 'Swiss standard grading system (6 best, 1 worst)',
                'type' => GradingSystem::TYPE_INTERNATIONAL,
                'scale_type' => GradingSystem::SCALE_NUMERIC,
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 60,
                'min_subjects_to_pass' => 5,
                'is_system_default' => true,
                'is_locked' => true,
                'is_active' => true,
            ]
        );

        $scales = [
            ['grade' => '6', 'grade_label' => 'Ausgezeichnet', 'min_score' => 95, 'max_score' => 100, 'gpa_points' => 6.0, 'remark' => 'Excellent', 'is_passing' => true, 'sort_order' => 1],
            ['grade' => '5.5', 'grade_label' => 'Sehr Gut', 'min_score' => 87, 'max_score' => 94.99, 'gpa_points' => 5.5, 'remark' => 'Very Good', 'is_passing' => true, 'sort_order' => 2],
            ['grade' => '5', 'grade_label' => 'Gut', 'min_score' => 80, 'max_score' => 86.99, 'gpa_points' => 5.0, 'remark' => 'Good', 'is_passing' => true, 'sort_order' => 3],
            ['grade' => '4.5', 'grade_label' => 'Befriedigend', 'min_score' => 70, 'max_score' => 79.99, 'gpa_points' => 4.5, 'remark' => 'Satisfactory', 'is_passing' => true, 'sort_order' => 4],
            ['grade' => '4', 'grade_label' => 'Genügend', 'min_score' => 60, 'max_score' => 69.99, 'gpa_points' => 4.0, 'remark' => 'Sufficient', 'is_passing' => true, 'sort_order' => 5],
            ['grade' => '3.5', 'grade_label' => 'Ungenügend', 'min_score' => 50, 'max_score' => 59.99, 'gpa_points' => 3.5, 'remark' => 'Insufficient', 'is_passing' => false, 'sort_order' => 6],
            ['grade' => '3', 'grade_label' => 'Schwach', 'min_score' => 40, 'max_score' => 49.99, 'gpa_points' => 3.0, 'remark' => 'Weak', 'is_passing' => false, 'sort_order' => 7],
            ['grade' => '2', 'grade_label' => 'Sehr Schwach', 'min_score' => 20, 'max_score' => 39.99, 'gpa_points' => 2.0, 'remark' => 'Very Weak', 'is_passing' => false, 'sort_order' => 8],
            ['grade' => '1', 'grade_label' => 'Unbrauchbar', 'min_score' => 0, 'max_score' => 19.99, 'gpa_points' => 1.0, 'remark' => 'Unusable', 'is_passing' => false, 'sort_order' => 9],
        ];

        foreach ($scales as $scale) {
            GradeScale::updateOrCreate(
                ['grading_system_id' => $system->id, 'grade' => $scale['grade']],
                $scale
            );
        }
    }
}
