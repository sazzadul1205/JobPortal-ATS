import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
}

interface UsersIndexProps {
    admins: User[];
    superAdmins: User[];
    employers: User[];
}

export default function Index({ admins, superAdmins, employers }: UsersIndexProps) {
    const sections = [
        { title: 'Admins', users: admins },
        { title: 'Super Admins', users: superAdmins },
        { title: 'Employers', users: employers },
    ];
    return (
        <AppLayout>
            <Head title="User Management" />
            <div className="mx-auto">
                <h1 className="mb-6 text-2xl font-bold text-slate-900">User Management</h1>
                {sections.map((section) => (
                    <div key={section.title} className="mb-8">
                        <h2 className="mb-3 text-lg font-semibold text-slate-900">{section.title}</h2>
                        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <table className="w-full text-sm">
                                <thead className="border-b border-slate-200 text-left text-xs text-slate-500 uppercase">
                                    <tr>
                                        <th className="px-5 py-3 font-medium">Name</th>
                                        <th className="px-5 py-3 font-medium">Email</th>
                                        <th className="px-5 py-3 font-medium">Role</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {section.users.map((user) => (
                                        <tr key={user.id} className="border-b border-slate-100 last:border-0">
                                            <td className="px-5 py-3 font-medium text-slate-900">{user.name}</td>
                                            <td className="px-5 py-3 text-slate-600">{user.email}</td>
                                            <td className="px-5 py-3 text-slate-600">{user.role}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ))}
            </div>
        </AppLayout>
    );
}
