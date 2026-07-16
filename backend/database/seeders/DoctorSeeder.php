<?php

namespace Database\Seeders;

use App\Enums\{BloodGroupOption, DepartmentRole, GenderOption, MaritalStatus};
use App\Enums\{LanguageOption};
use App\Models\{Department, DepartmentDoctor, Doctor, User};
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = [
            [
                'first_name' => 'Dr. Sushil',
                'last_name' => 'Gupta',
                'email' => 'dr.sushilgupta@gmail.com',
                'gender' => GenderOption::MALE->value,
                'department_name' => 'Pulmonologist',
                'city' => 'Ludhiana',
                'state' => 'Punjab',
                'career_start_year' => 2022,
                'sub_title' => 'MBBS, MD, DNB - Pulmonology',
                'medical_license_number' => '2005081408',
                'languages_known' => [LanguageOption::ENGLISH->value, LanguageOption::HINDI->value, LanguageOption::PUNJABI->value, LanguageOption::TELUGU->value],
                'bio' => 'Dr. Sushil Gupta is a senior consultant Pulmonologist and General Physician serving Ludhiana
and the wider Punjab region. Trained at premier medical institutions and certified in
Respiratory Diseases (DNB) and Critical Care (IDCCM), he blends modern diagnostics with a
warm, listening approach — so every patient feels heard, understood and cared for.
As Additional Director at Fortis Hospital, Ludhiana, he leads complex respiratory cases while
running his private clinic for patients who prefer a more personal, unhurried consultation. His
philosophy is simple — treat the person, not just the report.',
                'description' => 'Senior consultant Pulmonologist and General Physician with extensive experience in treating
respiratory and general medical conditions. Dedicated to evidence-based care and personalised
patient management.',
                'specializations_info' => 'Pneumonia, Bronchial Asthma, COPD, Interstitial Lung Diseases (ILD), Tuberculosis,
Lung Cancer, Sleep Disorders (Obstructive Sleep Apnea), Pleural Effusion, Bronchoscopy',
                'key_procedures_info' => 'Bronchoscopy with Biopsy and Bronchoalveolar Lavage (BAL),
Thoracentesis (pleural fluid aspiration), Ultrasound-guided FNAC of lung/mediastinal,
Non-invasive Ventilation (CPAP/BiPAP) for sleep apnoea and respiratory failure,
Pulmonary Function Tests (PFT) interpretation and management',
                'memberships_info' => 'Indian Chest Society, Indian Association of Bronchology, Indian Society of Critical Care Medicine, Association of Physicians of India, Indian Medical Association (IMA)',
                'availability_info' => 'Fortis Hospital, Ludhiana (Mon-Fri 10 AM – 5 PM),
Private Clinic, Sarabha Nagar (Mon,Wed,Fri 6 PM – 8 PM),Sat – 10 AM – 2 PM',
                'professional_experience_info' => [
                    [
                        'title' => 'Senior Residency',
                        'institution' => 'OMFS, CDC',
                        'year_started' => '2022',
                        'description' => 'Focused clinical work in oral oncology, head and neck surgery, and maxillofacial reconstruction.',
                    ],
                ],
            ],
        ];

        foreach ($doctors as $index => $entry) {
            $user = User::firstOrNew(['email' => strtolower($entry['email'])]);
            $user->name = trim($entry['first_name'] . ' ' . $entry['last_name']);
            $user->slug = Str::slug($user->name);
            $user->email_verified_at = now();
            $user->phone = '98' . str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT);
            $user->password = Hash::make('password');
            $user->status = 'active';
            $user->save();

            try {
                $user->assignRole('doctor');
            } catch (\Throwable $e) {
            }

            $doctor = Doctor::firstOrNew(['user_id' => $user->id]);
            $doctor->fill([
                'first_name' => $entry['first_name'],
                'last_name' => $entry['last_name'],
                'gender' => $entry['gender'],
                'dob' => Carbon::now()->subYears(45 + ($index * 12))->format('Y-m-d'),
                'marital_status' => MaritalStatus::MARRIED->value,
                'blood_group' => BloodGroupOption::A_POSITIVE->value,
                'career_start_year' => $entry['career_start_year'],
                'medical_license_number' => $entry['medical_license_number'],
                'bio' => $entry['bio'],
                'sub_title' => $entry['sub_title'] ?? null,
                'description' => $entry['description'],
                'address_line1' => 'Christian Medical College, Ludhiana',
                'country' => 'India',
                'state' => $entry['state'],
                'city' => $entry['city'],
                'pincode' => '141008',
                'languages_known' => $entry['languages_known'],
                'specializations_info' => $entry['specializations_info'],
                'key_procedures_info' => $entry['key_procedures_info'],
                'memberships_info' => $entry['memberships_info'],
                'availability_info' => $entry['availability_info'],
                'professional_experience_info' => $entry['professional_experience_info'],
                'education_info' => [],
                'fellowships_info' => [],
                'certifications_info' => [],
                'status' => \App\Enums\DoctorStatus::ACTIVE->value,
                'avatar' => asset('images/old-user-avatar.png'),
            ]);
            $doctor->save();

            $department = Department::firstOrCreate(
                ['name' => $entry['department_name']],
                [
                    'description' => $entry['department_name'] . ' department',
                    'is_tab_layout' => false,
                    'department_stamp' => null,
                    'symptom_ids' => [],
                ]
            );

            DepartmentDoctor::updateOrCreate(
                ['doctor_id' => $doctor->id, 'department_id' => $department->id],
                ['role' => DepartmentRole::SeniorConsultant->value, 'order' => 1]
            );
        }
    }
}
