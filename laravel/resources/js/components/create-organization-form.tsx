import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export function CreateOrganizationForm({ title, body }: { title: string; body: string }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('organizations.store'));
    };

    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border mx-auto w-full max-w-md rounded-xl border p-6">
            <h2 className="text-lg font-semibold">{title}</h2>
            <p className="text-muted-foreground mt-1 text-sm">{body}</p>

            <form onSubmit={submit} className="mt-4 space-y-4">
                <div className="grid gap-2">
                    <Label htmlFor="name">Nama organisasi</Label>
                    <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="Karang Taruna Melati"
                        autoFocus
                    />
                    <InputError message={errors.name} />
                </div>

                <Button type="submit" disabled={processing}>
                    Buat organisasi
                </Button>
            </form>
        </div>
    );
}
