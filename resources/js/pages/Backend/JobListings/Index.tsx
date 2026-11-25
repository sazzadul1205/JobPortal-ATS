import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface JobListing {
    id: number;
    title: string;
    is_active: boolean;
}

interface JobListingsIndexProps {
    jobListings: JobListing[] | { data: JobListing[] };
}

export default function Index({ jobListings }: JobListingsIndexProps) {
    const list = Array.isArray(jobListings) ? jobListings : (jobListings?.data ?? []);
    return (
        <AppLayout>
            <Head title="Job Listings" />
            <div className="mx-auto">
                <h1 className="mb-6 text-2xl font-bold text-slate-900">Job Listings</h1>
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="w-full text-sm">
                        <thead className="border-b border-slate-200 text-left text-xs text-slate-500 uppercase">
                            <tr>
                                <th className="px-5 py-3 font-medium">Title</th>
                                <th className="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {list.map((job) => (
                                <tr key={job.id} className="border-b border-slate-100 last:border-0">
                                    <td className="px-5 py-3 font-medium text-slate-900">{job.title}</td>
                                    <td className="px-5 py-3 text-slate-600">{job.is_active ? 'Active' : 'Inactive'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
