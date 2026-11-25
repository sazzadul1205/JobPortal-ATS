<?php

namespace Database\Seeders;

use App\Models\ApplicantProfile;
use App\Models\Application;
use App\Models\JobCategory;
use App\Models\JobListing;
use App\Models\Location;
use App\Models\User;
use Database\Seeders\RBAC\RBACSeeder;
use Database\Seeders\RBAC\UserRolesSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        // 1. Seed RBAC (roles, permissions, module access)
        $this->command->info('Seeding RBAC...');
        $this->call(RBACSeeder::class);

        // 2. Create Job Categories (22 categories)
        $this->command->info('Creating job categories...');
        $categories = $this->createJobCategories();

        // 3. Create Locations (9 Bangladesh divisions + All Over Bangladesh)
        $this->command->info('Creating locations...');
        $locations = $this->createLocations();

        // 4. Create Users and assign roles via RBAC
        $this->command->info('Creating users...');
        $superAdmin = $this->createSuperAdmin();
        $admin = $this->createAdmin();
        $employers = $this->createEmployers(10);
        $jobSeekers = $this->createJobSeekers(50);

        // 5. Create Applicant Profiles for job seekers
        $this->command->info('Creating applicant profiles...');
        $applicantProfiles = $this->createApplicantProfiles($jobSeekers);

        // 6. Create Job Listings
        $this->command->info('Creating job listings...');
        $jobListings = $this->createJobListings($employers, $categories, $locations, 100);

        // 7. Create Applications
        $this->command->info('Creating applications...');
        $this->createApplications($jobSeekers, $jobListings, $applicantProfiles, 300);

        // 8. Assign user roles via UserRolesSeeder
        $this->command->info('Assigning user roles...');
        $this->call(UserRolesSeeder::class);

        $this->command->info('Database seeding completed successfully!');
        $this->command->info('Summary:');
        $this->command->info('  - Job Categories: '.JobCategory::count());
        $this->command->info('  - Locations: '.Location::count());
        $this->command->info('  - Users: '.User::count());
        $this->command->info('  - Applicant Profiles: '.ApplicantProfile::count());
        $this->command->info('  - Job Listings: '.JobListing::count());
        $this->command->info('  - Applications: '.Application::count());
    }

    private function createJobCategories(): array
    {
        $categoryNames = [
            'Information Technology', 'Engineering', 'Healthcare', 'Finance & Accounting',
            'Sales & Marketing', 'Human Resources', 'Administration', 'Education & Training',
            'Customer Service', 'Manufacturing', 'Construction', 'Logistics & Transportation',
            'Retail', 'Hospitality & Tourism', 'Media & Communications', 'Legal',
            'Real Estate', 'Agriculture', 'Telecommunications', 'Automotive',
            'Energy & Utilities', 'Non-Profit & Social Services',
        ];

        $categories = [];
        foreach ($categoryNames as $name) {
            $categories[] = JobCategory::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'slug' => Str::slug($name), 'is_active' => true]
            );
        }

        return $categories;
    }

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

    private function createSuperAdmin(): User
    {
        return User::firstOrCreate(
            ['email' => 'superadmin@jobportal.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
    }

    private function createAdmin(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@jobportal.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
    }

    private function createEmployers(int $count): array
    {
        $employers = [];

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
                    'email_verified_at' => now(),
                ]
            );
        }

        for ($i = count($seededEmployers); $i < $count; $i++) {
            $employers[] = User::factory()->create([
                'email_verified_at' => now(),
            ]);
        }

        return $employers;
    }

    private function createJobSeekers(int $count): array
    {
        $jobSeekers = [];

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
                    'email_verified_at' => now(),
                ]
            );
        }

        for ($i = count($seededSeekers); $i < $count; $i++) {
            $jobSeekers[] = User::factory()->create([
                'email_verified_at' => now(),
            ]);
        }

        return $jobSeekers;
    }

    private function createApplicantProfiles(array $jobSeekers): array
    {
        $profiles = [];

        foreach ($jobSeekers as $seeker) {
            if (fake()->boolean(80)) {
                $profiles[] = ApplicantProfile::factory()->create(['user_id' => $seeker->id]);
            }
        }

        return $profiles;
    }

    private function createJobListings(array $employers, array $categories, array $locations, int $count): array
    {
        $jobListings = [];

        $featuredJobs = [
            ['title' => 'Senior Full Stack Developer', 'job_type' => 'full-time', 'experience_level' => 'senior', 'salary_min' => 180000, 'salary_max' => 250000],
            ['title' => 'Frontend Developer (React/Vue)', 'job_type' => 'full-time', 'experience_level' => 'mid', 'salary_min' => 120000, 'salary_max' => 180000],
            ['title' => 'Backend Engineer (Node.js/Go)', 'job_type' => 'full-time', 'experience_level' => 'senior', 'salary_min' => 200000, 'salary_max' => 300000],
            ['title' => 'DevOps Engineer', 'job_type' => 'full-time', 'experience_level' => 'mid', 'salary_min' => 150000, 'salary_max' => 220000],
            ['title' => 'Mobile App Developer (Flutter)', 'job_type' => 'full-time', 'experience_level' => 'mid', 'salary_min' => 100000, 'salary_max' => 150000],
        ];

        foreach ($featuredJobs as $index => $job) {
            $listing = JobListing::factory()->create(array_merge($job, [
                'user_id' => $employers[$index % count($employers)]->id,
                'category_id' => $categories[0]->id,
                'is_active' => true,
            ]));
            $listing->locations()->attach([$locations[1]->id]);
            $jobListings[] = $listing;
        }

        for ($i = count($featuredJobs); $i < $count; $i++) {
            $listing = JobListing::factory()->create([
                'user_id' => fake()->randomElement($employers)->id,
                'category_id' => fake()->randomElement($categories)->id,
            ]);
            $listing->locations()->attach([fake()->randomElement($locations)->id]);
            $jobListings[] = $listing;
        }

        for ($i = 0; $i < 10; $i++) {
            $listing = JobListing::factory()->inactive()->create([
                'user_id' => fake()->randomElement($employers)->id,
                'category_id' => fake()->randomElement($categories)->id,
            ]);
            $listing->locations()->attach([fake()->randomElement($locations)->id]);
            $jobListings[] = $listing;
        }

        for ($i = 0; $i < 5; $i++) {
            $listing = JobListing::factory()->expired()->create([
                'user_id' => fake()->randomElement($employers)->id,
                'category_id' => fake()->randomElement($categories)->id,
            ]);
            $listing->locations()->attach([fake()->randomElement($locations)->id]);
            $jobListings[] = $listing;
        }

        for ($i = 0; $i < 5; $i++) {
            $listing = JobListing::factory()->scheduled()->create([
                'user_id' => fake()->randomElement($employers)->id,
                'category_id' => fake()->randomElement($categories)->id,
            ]);
            $listing->locations()->attach([fake()->randomElement($locations)->id]);
            $jobListings[] = $listing;
        }

        return $jobListings;
    }

    private function createApplications(array $jobSeekers, array $jobListings, array $applicantProfiles, int $count): void
    {
        $activeListings = array_filter($jobListings, fn ($j) => $j->is_active && $j->application_deadline >= now());

        foreach ($activeListings as $listing) {
            $applicationCount = fake()->numberBetween(1, 8);
            $availableSeekers = $jobSeekers;
            shuffle($availableSeekers);

            for ($i = 0; $i < min($applicationCount, count($availableSeekers)); $i++) {
                $seeker = $availableSeekers[$i];
                $profile = collect($applicantProfiles)->firstWhere('user_id', $seeker->id);

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

        $states = [
            'pending' => 50, 'shortlisted' => 20, 'rejected' => 15, 'hired' => 10,
            'withAtsScore' => 40, 'highAtsScore' => 15, 'lowAtsScore' => 10,
        ];

        foreach ($states as $state => $stateCount) {
            for ($i = 0; $i < $stateCount; $i++) {
                $listing = fake()->randomElement($activeListings);
                $seeker = fake()->randomElement($jobSeekers);
                $profile = collect($applicantProfiles)->firstWhere('user_id', $seeker->id);

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
