import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface Candidate {
    id: number;
    name: string;
    email: string;
}

interface CandidatesIndexProps {
    candidates: Candidate[];
}

export default function CandidatesIndex({ candidates }: CandidatesIndexProps) {
    return (
        <AppLayout>
            <Head title="Candidates" />
            <div className="mx-auto">
                <h1 className="mb-6 text-2xl font-bold text-slate-900">Candidates</h1>
                <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table className="w-full text-sm">
                        <thead className="border-b border-slate-200 text-left text-xs text-slate-500 uppercase">
                            <tr>
                                <th className="px-5 py-3 font-medium">Name</th>
                                <th className="px-5 py-3 font-medium">Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            {candidates.map((candidate) => (
                                <tr key={candidate.id} className="border-b border-slate-100 last:border-0">
                                    <td className="px-5 py-3 font-medium text-slate-900">{candidate.name}</td>
                                    <td className="px-5 py-3 text-slate-600">{candidate.email}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
