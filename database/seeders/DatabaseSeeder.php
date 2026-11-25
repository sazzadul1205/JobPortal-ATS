<?php

namespace Database\Seeders;

use App\Models\ApplicantProfile;
use App\Models\Application;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        // 1. Create Job Categories (22 categories)
        $this->command->info('Creating job categories...');
        $categories = $this->createJobCategories();

        // 2. Create Locations (9 Bangladesh divisions + All Over Bangladesh)
        $this->command->info('Creating locations...');
        $locations = $this->createLocations();

        // 3. Create Users
        $this->command->info('Creating users...');
        $admin = $this->createAdmin();
        $employers = $this->createEmployers(10);
        $jobSeekers = $this->createJobSeekers(50);

        // 4. Create Applicant Profiles for job seekers
        $this->command->info('Creating applicant profiles...');
        $applicantProfiles = $this->createApplicantProfiles($jobSeekers);

        // 5. Create Job Listings
        $this->command->info('Creating job listings...');
        $jobListings = $this->createJobListings($employers, $categories, $locations, 100);

        // 6. Create Applications
        $this->command->info('Creating applications...');
        $this->createApplications($jobSeekers, $jobListings, $applicantProfiles, 300);

        $this->command->info('Database seeding completed successfully!');
        $this->command->info('Summary:');
        $this->command->info('  - Job Categories: '.JobCategory::count());
        $this->command->info('  - Locations: '.Location::count());
        $this->command->info('  - Users: '.User::count().' (1 admin, 10 employers, 50 job seekers)');
        $this->command->info('  - Applicant Profiles: '.ApplicantProfile::count());
        $this->command->info('  - Job Listings: '.JobListing::count());
        $this->command->info('  - Applications: '.Application::count());
    }

    /**
     * Create job categories.
     */
    private function createJobCategories(): array
    {
        $categoryNames = [
            'Information Technology',
            'Engineering',
            'Healthcare',
            'Finance & Accounting',
            'Sales & Marketing',
            'Human Resources',
            'Administration',
            'Education & Training',
            'Customer Service',
            'Manufacturing',
            'Construction',
            'Logistics & Transportation',
            'Retail',
            'Hospitality & Tourism',
            'Media & Communications',
            'Legal',
            'Real Estate',
            'Agriculture',
            'Telecommunications',
            'Automotive',
            'Energy & Utilities',
            'Non-Profit & Social Services',
        ];

        $categories = [];
        foreach ($categoryNames as $name) {
            $categories[] = JobCategory::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'is_active' => true,
                ]
            );
        }

        return $categories;
    }

    /**
     * Create locations.
     */
    private function createLocations(): array
    {
        $locationData = [
            ['name' => 'All Over Bangladesh', 'address' => null],
            ['name' => 'Dhaka', 'address' => 'Dhaka Division, Bangladesh'],
            ['name' => 'Chittagong', 'address' => 'Chittagong Division, Bangladesh'],
            ['name' => 'Sylhet', 'address' => 'Sylhet Division, Bangladesh'],
            ['name' => 'Rajshahi', 'address' => 'Rajshahi Division, Bangladesh'],
            ['name' => 'Khulna', 'address' => 'Khulna Division, Bangladesh'],
            ['name' => 'Barisal', 'address' => 'Barisal Division, Bangladesh'],
            ['name' => 'Rangpur', 'address' => 'Rangpur Division, Bangladesh'],
            ['name' => 'Mymensingh', 'address' => 'Mymensingh Division, Bangladesh'],
        ];

        $locations = [];
        foreach ($locationData as $data) {
            $locations[] = Location::firstOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['is_active' => true])
            );
        }

        return $locations;
    }

    /**
     * Create admin user.
     */
    private function createAdmin(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@jobportal.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Create employer users.
     */
    private function createEmployers(int $count): array
    {
        $employers = [];

        // Create specific seeded employers
        $seededEmployers = [
            ['name' => 'TechCorp Solutions', 'email' => 'hr@techcorp.com'],
            ['name' => 'DataSoft Systems', 'email' => 'careers@datasoft.com'],
            ['name' => 'InnovateBD Ltd', 'email' => 'jobs@innovatebd.com'],
            ['name' => 'Digital Bangladesh Inc', 'email' => 'recruitment@digitalbd.com'],
            ['name' => 'CloudNine Technologies', 'email' => 'hr@cloudnine.io'],
        ];

        foreach ($seededEmployers as $emp) {
            $employers[] = User::firstOrCreate(
                ['email' => $emp['email']],
                [
                    'name' => $emp['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'employer',
                    'email_verified_at' => now(),
                ]
            );
        }

        // Create additional random employers
        for ($i = count($seededEmployers); $i < $count; $i++) {
            $employers[] = User::factory()->employer()->create([
                'email_verified_at' => now(),
            ]);
        }

        return $employers;
    }

    /**
     * Create job seeker users.
     */
    private function createJobSeekers(int $count): array
    {
        $jobSeekers = [];

        // Create specific seeded job seekers
        $seededSeekers = [
            ['name' => 'Rahim Ahmed', 'email' => 'rahim.ahmed@email.com'],
            ['name' => 'Fatima Begum', 'email' => 'fatima.begum@email.com'],
            ['name' => 'Karim Hassan', 'email' => 'karim.hassan@email.com'],
            ['name' => 'Ayesha Rahman', 'email' => 'ayesha.rahman@email.com'],
            ['name' => 'Mohammad Ali', 'email' => 'mohammad.ali@email.com'],
        ];

        foreach ($seededSeekers as $seeker) {
            $jobSeekers[] = User::firstOrCreate(
                ['email' => $seeker['email']],
                [
                    'name' => $seeker['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'job_seeker',
                    'email_verified_at' => now(),
                ]
            );
        }

        // Create additional random job seekers
        for ($i = count($seededSeekers); $i < $count; $i++) {
            $jobSeekers[] = User::factory()->jobSeeker()->create([
                'email_verified_at' => now(),
            ]);
        }

        return $jobSeekers;
    }

    /**
     * Create applicant profiles for job seekers.
     */
    private function createApplicantProfiles(array $jobSeekers): array
    {
        $profiles = [];

        foreach ($jobSeekers as $seeker) {
            // 80% of job seekers have profiles
            if (fake()->boolean(80)) {
                $profiles[] = ApplicantProfile::factory()->create([
                    'user_id' => $seeker->id,
                    'email' => $seeker->email,
                ]);
            }
        }

        return $profiles;
    }

    /**
     * Create job listings.
     */
    private function createJobListings(array $employers, array $categories, array $locations, int $count): array
    {
        $jobListings = [];

        // Create specific featured job listings
        $featuredJobs = [
            [
                'title' => 'Senior Full Stack Developer',
                'job_type' => 'full-time',
                'experience_level' => 'senior',
                'salary' => 'BDT 180,000 - 250,000',
            ],
            [
                'title' => 'Frontend Developer (React/Vue)',
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'salary' => 'BDT 120,000 - 180,000',
            ],
            [
                'title' => 'Backend Engineer (Node.js/Go)',
                'job_type' => 'full-time',
                'experience_level' => 'senior',
                'salary' => 'BDT 200,000 - 300,000',
            ],
            [
                'title' => 'DevOps Engineer',
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'salary' => 'BDT 150,000 - 220,000',
            ],
            [
                'title' => 'Mobile App Developer (Flutter)',
                'job_type' => 'full-time',
                'experience_level' => 'mid',
                'salary' => 'BDT 100,000 - 150,000',
            ],
        ];

        foreach ($featuredJobs as $index => $job) {
            $jobListings[] = JobListing::factory()->create(array_merge($job, [
                'user_id' => $employers[$index % count($employers)]->id,
                'category_id' => $categories[0]->id, // IT category
                'location_id' => $locations[1]->id, // Dhaka
                'is_active' => true,
                'schedule_start_date' => null,
            ]));
        }

        // Create remaining random job listings
        for ($i = count($featuredJobs); $i < $count; $i++) {
            $jobListings[] = JobListing::factory()->create([
                'user_id' => fake()->randomElement($employers)->id,
                'category_id' => fake()->randomElement($categories)->id,
                'location_id' => fake()->randomElement($locations)->id,
            ]);
        }

        // Add some inactive/expired/scheduled listings
        for ($i = 0; $i < 10; $i++) {
            $jobListings[] = JobListing::factory()->inactive()->create([
                'user_id' => fake()->randomElement($employers)->id,
                'category_id' => fake()->randomElement($categories)->id,
                'location_id' => fake()->randomElement($locations)->id,
            ]);
        }

        for ($i = 0; $i < 5; $i++) {
            $jobListings[] = JobListing::factory()->expired()->create([
                'user_id' => fake()->randomElement($employers)->id,
                'category_id' => fake()->randomElement($categories)->id,
                'location_id' => fake()->randomElement($locations)->id,
            ]);
        }

        for ($i = 0; $i < 5; $i++) {
            $jobListings[] = JobListing::factory()->scheduled()->create([
                'user_id' => fake()->randomElement($employers)->id,
                'category_id' => fake()->randomElement($categories)->id,
                'location_id' => fake()->randomElement($locations)->id,
            ]);
        }

        return $jobListings;
    }

    /**
     * Create applications.
     */
    private function createApplications(array $jobSeekers, array $jobListings, array $applicantProfiles, int $count): void
    {
        $activeListings = array_filter($jobListings, fn ($j) => $j->is_active && $j->application_deadline >= now());

        // Create applications for active job listings
        foreach ($activeListings as $listing) {
            // Each listing gets 1-8 applications
            $applicationCount = fake()->numberBetween(1, 8);

            $availableSeekers = $jobSeekers;
            shuffle($availableSeekers);

            for ($i = 0; $i < min($applicationCount, count($availableSeekers)); $i++) {
                $seeker = $availableSeekers[$i];
                $profile = collect($applicantProfiles)->firstWhere('user_id', $seeker->id);

                // Check if application already exists
                $exists = Application::where('job_listing_id', $listing->id)
                    ->where('user_id', $seeker->id)
                    ->exists();

                if (! $exists) {
                    Application::factory()->create([
                        'job_listing_id' => $listing->id,
                        'user_id' => $seeker->id,
                        'applicant_profile_id' => $profile?->id,
                        'name' => $seeker->name,
                        'email' => $seeker->email,
                    ]);
                }
            }
        }

        // Create additional applications with specific states
        $states = [
            'pending' => 50,
            'reviewed' => 30,
            'shortlisted' => 20,
            'rejected' => 15,
            'hired' => 10,
            'withAtsScore' => 40,
            'highAtsScore' => 15,
            'lowAtsScore' => 10,
        ];

        foreach ($states as $state => $stateCount) {
            for ($i = 0; $i < $stateCount; $i++) {
                $listing = fake()->randomElement($activeListings);
                $seeker = fake()->randomElement($jobSeekers);
                $profile = collect($applicantProfiles)->firstWhere('user_id', $seeker->id);

                // Check if application already exists
                $exists = Application::where('job_listing_id', $listing->id)
                    ->where('user_id', $seeker->id)
                    ->exists();

                if (! $exists) {
                    Application::factory()->{$state}()->create([
                        'job_listing_id' => $listing->id,
                        'user_id' => $seeker->id,
                        'applicant_profile_id' => $profile?->id,
                        'name' => $seeker->name,
                        'email' => $seeker->email,
                    ]);
                }
            }
        }
    }
}
