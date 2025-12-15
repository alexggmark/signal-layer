import { cn } from '@/lib/utils';

interface Column<T> {
    key: keyof T | string;
    header: string;
    className?: string;
    render?: (value: T[keyof T], row: T) => React.ReactNode;
}

interface DataTableProps<T> {
    columns: Column<T>[];
    data: T[];
    className?: string;
    emptyMessage?: string;
}

export function DataTable<T extends Record<string, unknown>>({
    columns,
    data,
    className,
    emptyMessage = 'No data available',
}: DataTableProps<T>) {
    if (data.length === 0) {
        return (
            <div className="flex h-[120px] items-center justify-center text-sm text-muted-foreground">
                {emptyMessage}
            </div>
        );
    }

    return (
        <div className={cn('overflow-x-auto', className)}>
            <table className="w-full text-sm">
                <thead>
                    <tr className="border-b">
                        {columns.map((column) => (
                            <th
                                key={String(column.key)}
                                className={cn(
                                    'pb-2 text-left font-medium text-muted-foreground',
                                    column.className,
                                )}
                            >
                                {column.header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {data.map((row, rowIndex) => (
                        <tr
                            key={rowIndex}
                            className="border-b last:border-0 hover:bg-muted/50"
                        >
                            {columns.map((column) => {
                                const value = row[column.key as keyof T];
                                return (
                                    <td
                                        key={String(column.key)}
                                        className={cn('py-2', column.className)}
                                    >
                                        {column.render
                                            ? column.render(value, row)
                                            : String(value ?? '')}
                                    </td>
                                );
                            })}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
