import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Edit() {
    return (
        <AppLayout>
            <Head title="Edit" />
            <div className="mx-auto max-w-7xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold text-slate-900">Edit</h1>
                <p className="text-slate-500">Placeholder page.</p>
            </div>
        </AppLayout>
    );
}
