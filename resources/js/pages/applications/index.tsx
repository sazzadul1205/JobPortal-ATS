import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface Application {
    id: number;
    status: string;
    user?: { name: string };
    job_listing?: { title: string };
}

interface ApplicationsIndexProps {
    applications: { data: Application[] };
}

export default function ApplicationsIndex({ applications }: ApplicationsIndexProps) {
    return (
        <AppLayout>
            <Head title="Applications" />
            <div className="mx-auto">
                <h1 className="mb-6 text-2xl font-bold text-slate-900">Applications</h1>
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="w-full text-sm">
                        <thead className="border-b border-slate-200 text-left text-xs text-slate-500 uppercase">
                            <tr>
                                <th className="px-5 py-3 font-medium">Candidate</th>
                                <th className="px-5 py-3 font-medium">Job</th>
                                <th className="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {applications.data.map((application) => (
                                <tr key={application.id} className="border-b border-slate-100 last:border-0">
                                    <td className="px-5 py-3 font-medium text-slate-900">{application.user?.name}</td>
                                    <td className="px-5 py-3 text-slate-600">{application.job_listing?.title}</td>
                                    <td className="px-5 py-3 text-slate-600">{application.status}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
