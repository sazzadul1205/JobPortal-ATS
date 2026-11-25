import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function JobAnalysis() {
    return (
        <AppLayout>
            <Head title="job-analysis" />
            <div className="mx-auto">
                <h1 className="mb-6 text-2xl font-bold text-slate-900">job-analysis</h1>
                <p className="text-slate-500">Placeholder page.</p>
            </div>
        </AppLayout>
    );
}
