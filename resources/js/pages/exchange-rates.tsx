import { Link, router, useForm } from '@inertiajs/react';
import { ArrowLeftRight, ArrowUpRight } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import { DailyExchangeRateChart } from '@/components/daily-exchange-rate-chart';
import type { DailyRate } from '@/components/daily-exchange-rate-chart';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import PublicLayout, {
    LanguageText,
    PublicPageHeading,
} from '@/layouts/public-layout';
import type { PublicLanguage } from '@/layouts/public-layout';
import { cn } from '@/lib/utils';
import { index } from '@/routes/exchange-rates';

const translations = {
    en: {
        title: 'Exchange rates',
        description: 'Convert currencies and view daily exchange rates.',
        converter: 'Currency converter',
        amount: 'Amount',
        sourceCurrency: 'From currency',
        targetCurrency: 'To currency',
        swap: 'Swap currencies',
        result: 'Estimated amount',
        invalidAmount: 'Enter an amount of zero or more.',
        crossRate:
            'Calculated using both currencies’ published KHR rates for the same date.',
        chart: 'Daily exchange-rate chart',
        provider: 'Source',
        archive: 'GDT rate archive',
        window: 'The chart shows up to 90 days. Only dates with a published rate are plotted.',
        effective: 'Effective date',
        synced: 'Last checked',
        history: 'Daily rates',
        historyDescription:
            'Rates by publication date, with the most recent first.',
        stale: 'Older rate',
        failed: 'The last sync failed. Previously verified rates are still shown when available.',
        unavailable: 'No published rate is available for this pair yet.',
        noResults: 'No rates were found for these dates.',
        source: 'View rate source',
        publisher: 'National Bank of Cambodia',
        from: 'From',
        to: 'To',
        apply: 'Apply filters',
        loading: 'Loading…',
        invalidDates: 'Enter valid dates with the start on or before the end.',
        previous: 'Previous',
        next: 'Next',
        page: 'Page',
        of: 'of',
    },
    km: {
        title: 'អត្រាប្តូរប្រាក់',
        description: 'ប្តូរប្រាក់ និងមើលអត្រាប្តូរប្រាក់ប្រចាំថ្ងៃ។',
        converter: 'គណនាអត្រាប្តូរប្រាក់',
        amount: 'ចំនួនទឹកប្រាក់',
        sourceCurrency: 'ពីរូបិយប័ណ្ណ',
        targetCurrency: 'ទៅរូបិយប័ណ្ណ',
        swap: 'ប្តូរទិសរូបិយប័ណ្ណ',
        result: 'ចំនួនទឹកប្រាក់ប៉ាន់ស្មាន',
        invalidAmount: 'បញ្ចូលចំនួនទឹកប្រាក់ចាប់ពីសូន្យឡើងទៅ។',
        crossRate: 'គណនាដោយប្រើអត្រាធៀបនឹងប្រាក់រៀលរបស់រូបិយប័ណ្ណទាំងពីរនៅថ្ងៃតែមួយ។',
        chart: 'ក្រាហ្វអត្រាប្តូរប្រាក់ប្រចាំថ្ងៃ',
        provider: 'ប្រភព',
        archive: 'បណ្ណសារអត្រាប្តូរប្រាក់ GDT',
        window: 'ក្រាហ្វបង្ហាញរហូតដល់ ៩០ ថ្ងៃ និងបង្ហាញតែកាលបរិច្ឆេទដែលមានការផ្សព្វផ្សាយអត្រា។',
        effective: 'កាលបរិច្ឆេទចូលជាធរមាន',
        synced: 'បានពិនិត្យចុងក្រោយ',
        history: 'អត្រាប្រចាំថ្ងៃ',
        historyDescription: 'អត្រាតាមកាលបរិច្ឆេទផ្សព្វផ្សាយ ដោយបង្ហាញថ្ងៃថ្មីបំផុតមុន។',
        stale: 'អត្រាចាស់',
        failed: 'ការធ្វើបច្ចុប្បន្នភាពចុងក្រោយបានបរាជ័យ។ អត្រាដែលបានផ្ទៀងផ្ទាត់ពីមុននៅតែបង្ហាញ ប្រសិនបើមាន។',
        unavailable: 'មិនទាន់មានអត្រាដែលបានផ្សព្វផ្សាយសម្រាប់រូបិយប័ណ្ណទាំងពីរនេះទេ។',
        noResults: 'មិនមានអត្រាសម្រាប់កាលបរិច្ឆេទទាំងនេះទេ។',
        source: 'មើលប្រភពអត្រា',
        publisher: 'ធនាគារជាតិនៃកម្ពុជា',
        from: 'ចាប់ពី',
        to: 'ដល់',
        apply: 'ស្វែងរក',
        loading: 'កំពុងផ្ទុក…',
        invalidDates: 'បញ្ចូលកាលបរិច្ឆេទត្រឹមត្រូវ ដោយថ្ងៃចាប់ផ្តើមមុន ឬស្មើនឹងថ្ងៃបញ្ចប់។',
        previous: 'មុន',
        next: 'បន្ទាប់',
        page: 'ទំព័រ',
        of: 'នៃ',
    },
};

type DisplayRate = DailyRate & {
    base_currency: string;
    quote_currency: string;
};

type PageProps = {
    pair: { base: string; quote: string };
    currencies: string[];
    latest: DisplayRate | null;
    dailyTrend: DailyRate[];
    history: (DisplayRate & { access_provider: string })[];
    archiveUrl: string | null;
    meta: {
        original_publisher: string;
        source_url: string;
        last_synced_at: string | null;
        is_stale: boolean;
        sync_failed: boolean;
    };
    pagination: { page: number; last_page: number };
    filters: { from: string | null; to: string | null };
    language: PublicLanguage;
};

const khmerMonths = [
    'មករា',
    'កុម្ភៈ',
    'មីនា',
    'មេសា',
    'ឧសភា',
    'មិថុនា',
    'កក្កដា',
    'សីហា',
    'កញ្ញា',
    'តុលា',
    'វិច្ឆិកា',
    'ធ្នូ',
];
const englishDate = new Intl.DateTimeFormat('en-KH', {
    dateStyle: 'medium',
    timeZone: 'Asia/Phnom_Penh',
});
const dateParts = new Intl.DateTimeFormat('en-US', {
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
    timeZone: 'Asia/Phnom_Penh',
});

function formatKhmerDate(date: Date): string {
    // Browser support for Khmer date formatting varies, including during hydration.
    const parts = new Map(
        dateParts.formatToParts(date).map(({ type, value }) => [type, value]),
    );
    const month = khmerMonths[Number(parts.get('month')) - 1];

    return `${parts.get('day')} ${month} ${parts.get('year')}`;
}

function CurrencySelect({
    id,
    label,
    value,
    currencies,
    names,
    disabled,
    onChange,
}: {
    id: string;
    label: ReactNode;
    value: string;
    currencies: string[];
    names: Intl.DisplayNames;
    disabled: boolean;
    onChange: (currency: string) => void;
}) {
    return (
        <div className="grid min-w-0 grid-cols-1 gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Select value={value} onValueChange={onChange} disabled={disabled}>
                <SelectTrigger
                    id={id}
                    className="w-full min-w-0 max-sm:gap-1 max-sm:px-2"
                >
                    <span aria-hidden="true" className="sm:hidden">
                        {value}
                    </span>
                    <span className="min-w-0 truncate max-sm:sr-only">
                        <SelectValue />
                    </span>
                </SelectTrigger>
                <SelectContent className="public-theme" avoidCollisions>
                    {currencies.map((currency) => {
                        const name = names.of(
                            currency === 'SDR' ? 'XDR' : currency,
                        );

                        return (
                            <SelectItem key={currency} value={currency}>
                                {name && name !== currency
                                    ? `${currency} · ${name}`
                                    : currency}
                            </SelectItem>
                        );
                    })}
                </SelectContent>
            </Select>
        </div>
    );
}

export default function ExchangeRates({
    pair,
    currencies,
    latest,
    dailyTrend,
    history,
    archiveUrl,
    meta,
    pagination,
    filters,
    language,
}: PageProps) {
    const copy = translations[language];
    const locale = language === 'km' ? 'km-KH' : 'en-KH';
    const [amount, setAmount] = useState('1');
    const [updatingPair, setUpdatingPair] = useState(false);
    const currencyNames = new Intl.DisplayNames(locale, {
        type: 'currency',
        fallback: 'none',
    });
    const formatRate = new Intl.NumberFormat(locale, {
        maximumFractionDigits: 10,
    });
    const form = useForm({
        from: filters.from ?? '',
        to: filters.to ?? '',
    });
    const alternateLanguage = language === 'km' ? 'en' : 'km';
    const pageLink = (page: number, lang: PublicLanguage = language) =>
        index({ query: { ...filters, ...pair, lang, page } });
    const sameCurrency = pair.base === pair.quote;
    const unitRate = sameCurrency ? 1 : latest ? Number(latest.rate) : null;
    const enteredAmount = amount === '' ? NaN : Number(amount);
    const converted = unitRate === null ? null : enteredAmount * unitRate;
    const resultFormatter = new Intl.NumberFormat(locale, {
        maximumSignificantDigits: 12,
    });
    let resultText = copy.unavailable;
    if (updatingPair) {
        resultText = copy.loading;
    } else if (converted !== null) {
        resultText =
            Number.isFinite(converted) && enteredAmount >= 0
                ? resultFormatter.format(converted) + ' ' + pair.quote
                : copy.invalidAmount;
    }

    const rateLabel = `${pair.quote} / ${pair.base}`;
    const pairLabel = `${pair.base}/${pair.quote}`;

    function translatedText(key: keyof typeof translations.en) {
        return (
            <LanguageText
                language={language}
                en={translations.en[key]}
                km={translations.km[key]}
            />
        );
    }

    function renderDate(date: string, label?: 'effective' | 'synced') {
        const time = new Date(date);
        const en = englishDate.format(time);
        const km = formatKhmerDate(time);

        return (
            <LanguageText
                language={language}
                en={label ? `${translations.en[label]} ${en}` : en}
                km={label ? `${translations.km[label]} ${km}` : km}
            />
        );
    }

    function changeCurrencies(base: string, quote: string) {
        setUpdatingPair(true);
        router.get(
            index.url({ query: { ...filters, base, quote, lang: language } }),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setUpdatingPair(false),
            },
        );
    }

    function filterHistory(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.get(index.url({ query: { ...pair, lang: language } }), {
            preserveScroll: true,
            preserveState: true,
        });
    }

    const selectedDates = filters.from !== null || filters.to !== null;

    return (
        <PublicLayout
            title={copy.title}
            language={language}
            section="rates"
            languageHref={pageLink(pagination.page, alternateLanguage)}
        >
            <PublicPageHeading
                icon={ArrowLeftRight}
                title={copy.title}
                description={copy.description}
            />

            <div
                className={cn(
                    'grid gap-5',
                    !sameCurrency &&
                        'lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]',
                )}
            >
                <Card className="min-w-0 rounded-2xl shadow-none">
                    <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-2">
                        <CardTitle>
                            <h2 className="text-lg leading-tight">
                                {translatedText('converter')}
                            </h2>
                        </CardTitle>
                        {meta.is_stale && (
                            <Badge variant="secondary">
                                {translatedText('stale')}
                            </Badge>
                        )}
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-5">
                        <div className="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-end gap-3">
                            <div className="col-span-full grid gap-2">
                                <Label htmlFor="exchange-amount">
                                    {copy.amount}
                                </Label>
                                <Input
                                    id="exchange-amount"
                                    type="number"
                                    min="0"
                                    step="any"
                                    inputMode="decimal"
                                    value={amount}
                                    onChange={(event) =>
                                        setAmount(event.target.value)
                                    }
                                />
                            </div>
                            <CurrencySelect
                                id="exchange-base"
                                label={translatedText('sourceCurrency')}
                                value={pair.base}
                                currencies={currencies}
                                names={currencyNames}
                                disabled={updatingPair || form.processing}
                                onChange={(currency) =>
                                    changeCurrencies(currency, pair.quote)
                                }
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                aria-label={copy.swap}
                                title={copy.swap}
                                disabled={
                                    updatingPair ||
                                    form.processing ||
                                    sameCurrency
                                }
                                onClick={() =>
                                    changeCurrencies(pair.quote, pair.base)
                                }
                            >
                                <ArrowLeftRight aria-hidden="true" />
                            </Button>
                            <CurrencySelect
                                id="exchange-quote"
                                label={translatedText('targetCurrency')}
                                value={pair.quote}
                                currencies={currencies}
                                names={currencyNames}
                                disabled={updatingPair || form.processing}
                                onChange={(currency) =>
                                    changeCurrencies(pair.base, currency)
                                }
                            />
                        </div>

                        <div
                            role="status"
                            aria-busy={updatingPair}
                            className="grid grid-cols-1 gap-1 rounded-xl border bg-accent/65 p-4 sm:p-5"
                        >
                            <p className="text-sm font-medium text-muted-foreground">
                                {translatedText('result')}
                            </p>
                            <p className="text-3xl font-semibold tracking-tight wrap-anywhere text-primary tabular-nums sm:text-4xl">
                                {resultText}
                            </p>
                            {latest && !updatingPair && (
                                <p className="text-sm text-muted-foreground">
                                    1 {pair.base} ={' '}
                                    {formatRate.format(Number(latest.rate))}{' '}
                                    {pair.quote}
                                    {' · '}
                                    <time dateTime={latest.effective_date}>
                                        {renderDate(
                                            latest.effective_date,
                                            'effective',
                                        )}
                                    </time>
                                </p>
                            )}
                        </div>

                        {latest &&
                            pair.base !== 'KHR' &&
                            pair.quote !== 'KHR' && (
                                <p className="text-sm text-muted-foreground">
                                    {translatedText('crossRate')}
                                </p>
                            )}
                        {meta.sync_failed && !sameCurrency && (
                            <p
                                role="status"
                                className="rounded-md border bg-muted px-3 py-2 text-sm"
                            >
                                {translatedText('failed')}
                            </p>
                        )}
                        {!sameCurrency && (
                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                <LanguageText
                                    language={language}
                                    en={meta.original_publisher}
                                    km={translations.km.publisher}
                                />
                                <a
                                    href={meta.source_url}
                                    className="inline-flex items-center gap-1 font-medium text-primary underline underline-offset-4"
                                >
                                    {translatedText('source')}{' '}
                                    <ArrowUpRight
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                </a>
                                {meta.last_synced_at && (
                                    <time
                                        dateTime={meta.last_synced_at}
                                        className="text-muted-foreground"
                                    >
                                        {renderDate(
                                            meta.last_synced_at,
                                            'synced',
                                        )}
                                    </time>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {!sameCurrency && (
                    <>
                        <Card className="min-w-0 rounded-2xl shadow-none">
                            <CardHeader>
                                <CardTitle>
                                    <h2 className="text-lg leading-tight">
                                        <LanguageText
                                            language={language}
                                            en={`${translations.en.chart} · ${pairLabel}`}
                                            km={`${translations.km.chart} · ${pairLabel}`}
                                        />
                                    </h2>
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    {translatedText('window')}
                                </p>
                            </CardHeader>
                            <CardContent
                                className="grid gap-5"
                                aria-busy={form.processing || updatingPair}
                            >
                                <form
                                    onSubmit={filterHistory}
                                    className="grid grid-cols-2 items-end gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]"
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="from">
                                            {copy.from}
                                        </Label>
                                        <Input
                                            id="from"
                                            name="from"
                                            type="date"
                                            value={form.data.from}
                                            max={form.data.to || undefined}
                                            onChange={(event) =>
                                                form.setData(
                                                    'from',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="to">{copy.to}</Label>
                                        <Input
                                            id="to"
                                            name="to"
                                            type="date"
                                            value={form.data.to}
                                            min={form.data.from || undefined}
                                            onChange={(event) =>
                                                form.setData(
                                                    'to',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        className="col-span-full sm:col-span-1"
                                        disabled={
                                            form.processing || updatingPair
                                        }
                                    >
                                        <LanguageText
                                            language={language}
                                            en={
                                                form.processing
                                                    ? translations.en.loading
                                                    : translations.en.apply
                                            }
                                            km={
                                                form.processing
                                                    ? translations.km.loading
                                                    : translations.km.apply
                                            }
                                        />
                                    </Button>
                                    <InputError
                                        role="alert"
                                        message={
                                            form.errors.from || form.errors.to
                                                ? copy.invalidDates
                                                : undefined
                                        }
                                        className="col-span-full"
                                    />
                                </form>

                                {dailyTrend.length ? (
                                    <DailyExchangeRateChart
                                        key={[
                                            pairLabel,
                                            filters.from,
                                            filters.to,
                                        ].join('|')}
                                        rateLabel={rateLabel}
                                        renderDate={renderDate}
                                        formatRate={formatRate}
                                        points={dailyTrend}
                                    />
                                ) : (
                                    <p className="rounded-md bg-muted px-4 py-5 text-sm text-muted-foreground">
                                        {selectedDates || latest
                                            ? copy.noResults
                                            : copy.unavailable}
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <Card className="min-w-0 rounded-2xl shadow-none lg:col-span-2">
                            <CardHeader>
                                <CardTitle>
                                    <h2 className="text-lg leading-tight">
                                        <LanguageText
                                            language={language}
                                            en={`${translations.en.history} · ${pairLabel}`}
                                            km={`${translations.km.history} · ${pairLabel}`}
                                        />
                                    </h2>
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    {translatedText('historyDescription')}
                                </p>
                                {archiveUrl && (
                                    <a
                                        href={archiveUrl}
                                        className="text-sm font-medium text-primary underline underline-offset-4"
                                    >
                                        {translatedText('archive')}
                                    </a>
                                )}
                            </CardHeader>
                            <CardContent className="grid gap-5">
                                {history.length === 0 ? (
                                    <p className="rounded-md bg-muted px-4 py-5 text-sm text-muted-foreground">
                                        {selectedDates || latest
                                            ? copy.noResults
                                            : copy.unavailable}
                                    </p>
                                ) : (
                                    <table className="w-full table-fixed text-sm">
                                        <caption className="sr-only">
                                            {copy.history}
                                        </caption>
                                        <colgroup>
                                            <col className="w-[44%]" />
                                            <col className="w-[36%]" />
                                            <col />
                                        </colgroup>
                                        <thead className="border-b text-left text-muted-foreground">
                                            <tr>
                                                <th
                                                    scope="col"
                                                    className="py-2 font-medium"
                                                >
                                                    <LanguageText
                                                        language={language}
                                                        en={
                                                            translations.en
                                                                .effective
                                                        }
                                                        km={
                                                            translations.km
                                                                .effective
                                                        }
                                                    />
                                                </th>
                                                <th
                                                    scope="col"
                                                    className="py-2 text-right font-medium"
                                                >
                                                    {rateLabel}
                                                </th>
                                                <th
                                                    scope="col"
                                                    className="py-2 pl-2 text-right font-medium sm:pl-4"
                                                >
                                                    <LanguageText
                                                        language={language}
                                                        en={
                                                            translations.en
                                                                .provider
                                                        }
                                                        km={
                                                            translations.km
                                                                .provider
                                                        }
                                                    />
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {history.map((entry) => (
                                                <tr key={entry.effective_date}>
                                                    <td className="py-3">
                                                        <time
                                                            dateTime={
                                                                entry.effective_date
                                                            }
                                                        >
                                                            {renderDate(
                                                                entry.effective_date,
                                                            )}
                                                        </time>
                                                    </td>
                                                    <td className="py-3 text-right break-words tabular-nums">
                                                        {formatRate.format(
                                                            Number(entry.rate) /
                                                                entry.unit,
                                                        )}{' '}
                                                        {entry.quote_currency}
                                                    </td>
                                                    <td className="py-3 pl-2 text-right text-muted-foreground sm:pl-4">
                                                        {entry.access_provider ===
                                                        'DataEF'
                                                            ? 'DataEF'
                                                            : 'GDT'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}

                                {(pagination.page > 1 ||
                                    pagination.last_page > 1) && (
                                    <nav
                                        aria-label={copy.history}
                                        className="flex flex-wrap items-center gap-3 text-sm"
                                    >
                                        {pagination.page > 1 && (
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                            >
                                                <Link
                                                    href={pageLink(
                                                        Math.min(
                                                            pagination.page - 1,
                                                            pagination.last_page,
                                                        ),
                                                    )}
                                                    preserveScroll
                                                    preserveState
                                                >
                                                    {copy.previous}
                                                </Link>
                                            </Button>
                                        )}
                                        <span className="text-muted-foreground">
                                            {copy.page} {pagination.page}{' '}
                                            {copy.of} {pagination.last_page}
                                        </span>
                                        {pagination.page <
                                            pagination.last_page && (
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                            >
                                                <Link
                                                    href={pageLink(
                                                        pagination.page + 1,
                                                    )}
                                                    preserveScroll
                                                    preserveState
                                                >
                                                    {copy.next}
                                                </Link>
                                            </Button>
                                        )}
                                    </nav>
                                )}
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </PublicLayout>
    );
}
