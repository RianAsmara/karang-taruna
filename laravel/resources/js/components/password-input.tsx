import { Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';

import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type PasswordInputProps = Omit<React.ComponentProps<typeof Input>, 'type'>;

export default function PasswordInput({ className, ...props }: PasswordInputProps) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="relative">
            <Input type={visible ? 'text' : 'password'} className={cn('pr-10', className)} {...props} />
            <button
                type="button"
                onClick={() => setVisible((v) => !v)}
                tabIndex={-1}
                className="text-muted-foreground hover:text-foreground absolute top-0 right-0 flex h-10 w-10 items-center justify-center"
                aria-label={visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
            >
                {visible ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
        </div>
    );
}
