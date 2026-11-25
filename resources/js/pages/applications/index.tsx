import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function ApplicationsIndex() {
    return (
        <AppLayout>
            <Head title="Applications" />
            <div className="mx-auto max-w-7xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold text-slate-900">Applications</h1>
                <p className="text-slate-500">Applications list will appear here.</p>
            </div>
        </AppLayout>
    );
}
