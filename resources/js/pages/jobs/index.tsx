import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';

interface Job {
    id: number;
    title: string;
    slug: string;
    description: string;
    job_type: string;
    experience_level: string;
    application_deadline: string;
    category?: { name: string };
    location?: { name: string };
}

interface JobsIndexProps {
    jobs: { data: Job[] };
    filters: Record<string, string>;
    categories: { id: number; name: string }[];
    locations: { id: number; name: string }[];
}

export default function JobsIndex({ jobs, filters, categories, locations }: JobsIndexProps) {
    return (
        <AppLayout>
            <Head title="Jobs" />
            <div className="mx-auto">
                <h1 className="mb-6 text-2xl font-bold text-slate-900">Available Jobs</h1>
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {jobs.data.map((job) => (
                        <div key={job.id} className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-900">{job.title}</h2>
                            <p className="mt-1 text-sm text-slate-500">
                                {job.category?.name} · {job.location?.name}
                            </p>
                            <p className="mt-2 line-clamp-3 text-sm text-slate-600">{job.description}</p>
                            <div className="mt-4 flex items-center justify-between">
                                <span className="text-xs text-slate-400">{job.job_type}</span>
                                <Link href={route('jobs.show', job)} className="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                                    View
                                </Link>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
