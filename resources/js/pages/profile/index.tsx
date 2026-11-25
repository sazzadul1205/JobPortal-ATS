import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function ProfileIndex() {
    const { props } = usePage<{ auth?: { user?: { name: string; email: string } } }>();
    const [name, setName] = useState(props.auth?.user?.name ?? '');
    const [email, setEmail] = useState(props.auth?.user?.email ?? '');
    const [phone, setPhone] = useState('');
    const [resume, setResume] = useState<File | null>(null);

    return (
        <AppLayout>
            <Head title="My Profile" />
            <div className="mx-auto max-w-3xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold text-slate-900">My Profile</h1>
                <form className="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Name</label>
                        <input value={name} onChange={(e) => setName(e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                        <input value={email} onChange={(e) => setEmail(e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Phone</label>
                        <input value={phone} onChange={(e) => setPhone(e.target.value)} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label className="mb-1.5 block text-sm font-medium text-slate-700">Resume</label>
                        <input type="file" onChange={(e) => setResume(e.target.files?.[0] ?? null)} className="w-full text-sm" />
                    </div>
                    <button type="submit" className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Save Profile
                    </button>
                </form>
            </div>
        </AppLayout>
    );
}
