import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface Location {
    id: number;
    name: string;
    is_active: boolean;
}

interface LocationsIndexProps {
    locations: Location[];
}

export default function Index({ locations }: LocationsIndexProps) {
    const list = Array.isArray(locations) ? locations : locations?.data ?? [];
    return (
        <AppLayout>
            <Head title="Locations" />
            <div className="mx-auto max-w-7xl px-4 py-8">
                <h1 className="mb-6 text-2xl font-bold text-slate-900">Locations</h1>
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="w-full text-sm">
                        <thead className="border-b border-slate-200 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th className="px-5 py-3 font-medium">Name</th>
                                <th className="px-5 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {list.map((location) => (
                                <tr key={location.id} className="border-b border-slate-100 last:border-0">
                                    <td className="px-5 py-3 font-medium text-slate-900">{location.name}</td>
                                    <td className="px-5 py-3 text-slate-600">{location.is_active ? 'Active' : 'Inactive'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}

