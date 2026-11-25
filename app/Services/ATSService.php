<?php

namespace App\Services;

use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class ATSService
{
    protected array $stopWords = [
        'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'as', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'a', 'an', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'my', 'your', 'his', 'her', 'its', 'our', 'their', 'me', 'him', 'us', 'them', 'what', 'which', 'who', 'whom', 'whose', 'where', 'when', 'why', 'how', 'all', 'each', 'every', 'both', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'just', 'now', 'then', 'here', 'there', 'also', 'back', 'after', 'before', 'over', 'under', 'again', 'further', 'once', 'out', 'up', 'down', 'off', 'on', 'above', 'below', 'between', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'to', 'from', 'up', 'down', 'in', 'out', 'on', 'off', 'over', 'under', 'again', 'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 's', 't', 'can', 'will', 'just', 'don', 'should', 'now', 'd', 'll', 'm', 'o', 're', 've', 'y', 'ain', 'aren', 'couldn', 'didn', 'doesn', 'hadn', 'hasn', 'haven', 'isn', 'ma', 'mightn', 'mustn', 'needn', 'shan', 'shouldn', 'wasn', 'weren', 'won', 'wouldn',
    ];

    public function calculateScore(Application $application, JobListing $jobListing): array
    {
        $resumeText = $this->extractResumeText($application);

        if (empty($resumeText)) {
            return $this->getEmptyScore();
        }

        $jobKeywords = $this->extractJobKeywords($jobListing);
        $matchedKeywords = [];
        $missingKeywords = [];

        foreach ($jobKeywords as $keyword) {
            if (stripos($resumeText, $keyword) !== false) {
                $matchedKeywords[] = $keyword;
            } else {
                $missingKeywords[] = $keyword;
            }
        }

        $totalKeywords = count($jobKeywords);
        $matchedCount = count($matchedKeywords);
        $percentage = $totalKeywords > 0 ? round(($matchedCount / $totalKeywords) * 100) : 0;

        $extractedSkills = $this->extractSkills($resumeText, $jobKeywords);
        $extractedExperienceYears = $this->extractExperienceYears($resumeText);
        $extractedEducation = $this->extractEducation($resumeText);

        $analysisDetails = $this->generateAnalysisDetails($matchedCount, $totalKeywords, $extractedSkills, $extractedExperienceYears, $extractedEducation, $jobListing);

        return [
            'total' => $percentage,
            'percentage' => $percentage,
            'matched_count' => $matchedCount,
            'total_keywords' => $totalKeywords,
            'extracted_skills' => $extractedSkills,
            'extracted_experience_years' => $extractedExperienceYears,
            'extracted_education' => $extractedEducation,
            'analysis_details' => $analysisDetails,
        ];
    }

    protected function extractResumeText(Application $application): string
    {
        if (empty($application->resume_path)) {
            return '';
        }

        $filePath = storage_path('app/public/'.$application->resume_path);

        if (! file_exists($filePath)) {
            return '';
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        try {
            return match (strtolower($extension)) {
                'pdf' => $this->extractPdfText($filePath),
                'doc', 'docx' => $this->extractWordText($filePath),
                default => '',
            };
        } catch (\Exception $e) {
            Log::error('Failed to extract resume text', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    protected function extractPdfText(string $filePath): string
    {
        $parser = new PdfParser;
        $pdf = $parser->parseFile($filePath);

        return $pdf->getText();
    }

    protected function extractWordText(string $filePath): string
    {
        $phpWord = WordIOFactory::load($filePath);
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText().' ';
                }
            }
        }

        return $text;
    }

    protected function extractJobKeywords(JobListing $jobListing): array
    {
        $keywords = [];

        if (! empty($jobListing->keywords) && is_array($jobListing->keywords)) {
            $keywords = array_merge($keywords, $jobListing->keywords);
        }

        if (! empty($jobListing->skills) && is_array($jobListing->skills)) {
            $keywords = array_merge($keywords, $jobListing->skills);
        }

        if (! empty($jobListing->responsibilities) && is_array($jobListing->responsibilities)) {
            foreach ($jobListing->responsibilities as $resp) {
                $keywords = array_merge($keywords, $this->extractKeywordsFromText($resp));
            }
        }

        if (! empty($jobListing->description)) {
            $keywords = array_merge($keywords, $this->extractKeywordsFromText($jobListing->description));
        }

        if (! empty($jobListing->requirements)) {
            $keywords = array_merge($keywords, $this->extractKeywordsFromText($jobListing->requirements));
        }

        return array_values(array_unique(array_filter($keywords, fn ($k) => strlen($k) > 2)));
    }

    protected function extractKeywordsFromText(string $text): array
    {
        $text = strip_tags($text);
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $keywords = [];
        foreach ($words as $word) {
            if (strlen($word) >= 3 && ! in_array($word, $this->stopWords)) {
                $keywords[] = $word;
            }
        }

        $frequency = array_count_values($keywords);
        arsort($frequency);

        return array_keys(array_slice($frequency, 0, 30, true));
    }

    protected function extractSkills(string $resumeText, array $jobKeywords): array
    {
        $commonSkills = [
            'javascript', 'typescript', 'react', 'vue', 'angular', 'node.js', 'nodejs', 'express', 'next.js', 'nextjs',
            'php', 'laravel', 'symfony', 'python', 'django', 'flask', 'fastapi', 'java', 'spring', 'spring boot',
            'c#', '.net', 'asp.net', 'go', 'golang', 'rust', 'ruby', 'rails', 'swift', 'kotlin', 'flutter', 'dart',
            'react native', 'expo', 'html', 'css', 'scss', 'sass', 'tailwind', 'bootstrap', 'webpack', 'vite',
            'postgresql', 'mysql', 'mongodb', 'redis', 'elasticsearch', 'sqlite', 'oracle', 'sql server',
            'docker', 'kubernetes', 'aws', 'azure', 'gcp', 'google cloud', 'terraform', 'ansible', 'jenkins',
            'git', 'github', 'gitlab', 'bitbucket', 'ci/cd', 'github actions', 'gitlab ci', 'circleci',
            'graphql', 'rest api', 'restful', 'microservices', 'system design', 'design patterns', 'solid',
            'agile', 'scrum', 'kanban', 'jira', 'confluence', 'testing', 'jest', 'phpunit', 'pytest', 'cypress',
            'linux', 'bash', 'shell', 'nginx', 'apache', 'message queues', 'rabbitmq', 'kafka', 'redis',
        ];

        $resumeLower = strtolower($resumeText);
        $foundSkills = [];

        foreach ($commonSkills as $skill) {
            if (stripos($resumeLower, $skill) !== false) {
                $foundSkills[] = $skill;
            }
        }

        foreach ($jobKeywords as $keyword) {
            if (stripos($resumeLower, strtolower($keyword)) !== false && ! in_array(strtolower($keyword), array_map('strtolower', $foundSkills))) {
                $foundSkills[] = $keyword;
            }
        }

        return array_values(array_unique($foundSkills));
    }

    protected function extractExperienceYears(string $resumeText): int
    {
        $patterns = [
            '/(\d+)\+?\s*years?\s*(?:of\s*)?experience/i',
            '/experience\s*:?\s*(\d+)\+?\s*years?/i',
            '/(\d+)\+?\s*yrs?\s*(?:of\s*)?experience/i',
            '/worked\s*(?:for|as)\s*\d+\+?\s*years?/i',
        ];

        $maxYears = 0;
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $resumeText, $matches)) {
                foreach ($matches[1] as $years) {
                    $maxYears = max($maxYears, (int) $years);
                }
            }
        }

        if ($maxYears === 0) {
            $datePattern = '/(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\s+\d{4}\s*[-–]\s*(?:present|current|(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\s+\d{4})/i';
            if (preg_match_all($datePattern, $resumeText, $matches)) {
                $maxYears = count($matches[0]);
            }
        }

        return min($maxYears, 50);
    }

    protected function extractEducation(string $resumeText): string
    {
        $educationLevels = [
            'phd' => ['phd', 'ph.d.', 'doctor of philosophy', 'doctorate'],
            'master' => ['master', 'm.s.', 'm.sc', 'msc', 'm.tech', 'mtech', 'mba', 'm.b.a.', 'master of'],
            'bachelor' => ['bachelor', 'b.s.', 'b.sc', 'bsc', 'b.tech', 'btech', 'b.e.', 'be', 'b.a.', 'ba', 'b.com', 'bcom', 'undergraduate'],
            'diploma' => ['diploma', 'polytechnic', 'associate degree'],
            'high-school' => ['high school', 'secondary school', 'hsc', 'ssc', 'a level', 'o level'],
        ];

        $resumeLower = strtolower($resumeText);

        foreach ($educationLevels as $level => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($resumeLower, $keyword) !== false) {
                    return $level;
                }
            }
        }

        return 'high-school';
    }

    protected function generateAnalysisDetails(int $matchedCount, int $totalKeywords, array $extractedSkills, int $experienceYears, string $education, JobListing $jobListing): array
    {
        $percentage = $totalKeywords > 0 ? round(($matchedCount / $totalKeywords) * 100) : 0;

        $level = match (true) {
            $percentage >= 80 => 'Excellent',
            $percentage >= 60 => 'Good',
            $percentage >= 40 => 'Fair',
            default => 'Needs Improvement',
        };

        $messages = [
            'Excellent' => 'Outstanding match with all critical requirements met.',
            'Good' => 'Strong match with most requirements satisfied.',
            'Fair' => 'Moderate match with some gaps in requirements.',
            'Needs Improvement' => 'Significant gaps in required skills and experience.',
        ];

        $requiredExp = $this->getRequiredExperience($jobListing->experience_level ?? '');
        $expMatch = $experienceYears >= $requiredExp;

        $requiredEdu = $this->getRequiredEducation($jobListing->education_requirement ?? '');
        $eduMatch = $this->educationMeetsRequirement($education, $requiredEdu);

        return [
            'level' => $level,
            'message' => $messages[$level] ?? $messages['Needs Improvement'],
            'matched_count' => $matchedCount,
            'missing_count' => $totalKeywords - $matchedCount,
            'top_matched' => array_slice($extractedSkills, 0, 5),
            'critical_missing' => array_slice(array_diff($jobListing->skills ?? [], $extractedSkills), 0, 5),
            'experience_match' => $expMatch,
            'education_match' => $eduMatch,
            'required_experience' => $requiredExp,
            'required_education' => $requiredEdu,
        ];
    }

    protected function getRequiredExperience(string $level): int
    {
        return match ($level) {
            'entry', 'intern' => 0,
            'junior' => 1,
            'mid' => 3,
            'senior' => 5,
            'lead', 'principal', 'executive' => 8,
            default => 2,
        };
    }

    protected function getRequiredEducation(string $education): string
    {
        return match ($education) {
            'phd' => 'phd',
            'master' => 'master',
            'bachelor' => 'bachelor',
            'diploma', 'associate' => 'diploma',
            'high-school' => 'high-school',
            default => 'bachelor',
        };
    }

    protected function educationMeetsRequirement(string $candidate, string $required): bool
    {
        $levels = ['high-school' => 1, 'diploma' => 2, 'bachelor' => 3, 'master' => 4, 'phd' => 5];

        return ($levels[$candidate] ?? 0) >= ($levels[$required] ?? 0);
    }

    protected function getEmptyScore(): array
    {
        return [
            'total' => 0,
            'percentage' => 0,
            'matched_count' => 0,
            'total_keywords' => 0,
            'extracted_skills' => [],
            'extracted_experience_years' => 0,
            'extracted_education' => 'high-school',
            'analysis_details' => [
                'level' => 'Needs Improvement',
                'message' => 'Unable to extract text from resume.',
                'matched_count' => 0,
                'missing_count' => 0,
                'top_matched' => [],
                'critical_missing' => [],
                'experience_match' => false,
                'education_match' => false,
                'required_experience' => 0,
                'required_education' => 'high-school',
            ],
        ];
    }

    public function getTopSkillsFromApplications(Collection $applications): array
    {
        $skillCounts = [];

        foreach ($applications as $application) {
            if ($application->ats_score && isset($application->ats_score['extracted_skills'])) {
                foreach ($application->ats_score['extracted_skills'] as $skill) {
                    $skillCounts[strtolower($skill)] = ($skillCounts[strtolower($skill)] ?? 0) + 1;
                }
            }
        }

        arsort($skillCounts);

        return array_slice($skillCounts, 0, 20, true);
    }

    public function getKeywordAnalysisData(JobListing $jobListing): array
    {
        $applications = $jobListing->applications()->whereNotNull('ats_score')->get();

        $keywordStats = [];
        $totalApplications = $applications->count();

        if ($totalApplications === 0) {
            return [];
        }

        $jobKeywords = $this->extractJobKeywords($jobListing);

        foreach ($jobKeywords as $keyword) {
            $matched = 0;
            foreach ($applications as $application) {
                if ($application->matched_keywords && in_array($keyword, $application->matched_keywords)) {
                    $matched++;
                }
            }
            $keywordStats[] = [
                'keyword' => $keyword,
                'matched_count' => $matched,
                'match_percentage' => round(($matched / $totalApplications) * 100),
            ];
        }

        usort($keywordStats, fn ($a, $b) => $b['match_percentage'] <=> $a['match_percentage']);

        return $keywordStats;
    }

    public function getTopCandidatesData(JobListing $jobListing, int $limit = 10): array
    {
        return $jobListing->applications()
            ->whereNotNull('ats_score')
            ->orderByRaw('JSON_EXTRACT(ats_score, "$.percentage") DESC')
            ->limit($limit)
            ->get()
            ->map(function ($application) {
                return [
                    'id' => $application->id,
                    'name' => $application->name,
                    'email' => $application->email,
                    'ats_score' => $application->ats_score['percentage'] ?? 0,
                    'status' => $application->status,
                    'applied_at' => $application->created_at,
                ];
            })
            ->toArray();
    }

    public function getATSStatsData(): array
    {
        $user = auth()->user();
        $query = Application::query();

        if ($user->role === 'employer') {
            $query->whereHas('jobListing', fn ($q) => $q->where('user_id', $user->id));
        }

        $applications = $query->whereNotNull('ats_score')->get();

        $totalApplications = $applications->count();
        $averageScore = $totalApplications > 0
            ? round($applications->avg(fn ($a) => $a->ats_score['percentage'] ?? 0))
            : 0;

        $distribution = [
            'Excellent (80-100)' => $applications->filter(fn ($a) => ($a->ats_score['percentage'] ?? 0) >= 80)->count(),
            'Good (60-79)' => $applications->filter(fn ($a) => ($a->ats_score['percentage'] ?? 0) >= 60 && ($a->ats_score['percentage'] ?? 0) < 80)->count(),
            'Fair (40-59)' => $applications->filter(fn ($a) => ($a->ats_score['percentage'] ?? 0) >= 40 && ($a->ats_score['percentage'] ?? 0) < 60)->count(),
            'Poor (0-39)' => $applications->filter(fn ($a) => ($a->ats_score['percentage'] ?? 0) < 40)->count(),
        ];

        $statusBreakdown = $applications->groupBy('status')->map->count()->toArray();

        $topSkills = $this->getTopSkillsFromApplications($applications);

        return [
            'total_applications' => $totalApplications,
            'average_score' => $averageScore,
            'score_distribution' => $distribution,
            'top_skills' => $topSkills,
            'status_breakdown' => $statusBreakdown,
        ];
    }
}
