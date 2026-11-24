import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowLeftRight, ArrowUpRight } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
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
import { home } from '@/routes';
import { index } from '@/routes/exchange-rates';

const translations = {
    en: {
        home: 'Home',
        title: 'Exchange rates',
        description:
            'Convert an amount and view daily rates for your selected currencies.',
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
        disclaimer: 'Independent public information platform.',
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
        home: 'ទំព័រដើម',
        title: 'អត្រាប្តូរប្រាក់',
        description: 'ប្តូរចំនួនទឹកប្រាក់ និងមើលអត្រាប្រចាំថ្ងៃសម្រាប់រូបិយប័ណ្ណដែលបានជ្រើសរើស។',
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
        disclaimer: 'វេទិកាព័ត៌មានសាធារណៈឯករាជ្យ។',
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

type Language = keyof typeof translations;

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
    language: Language;
};

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
    label: string;
    value: string;
    currencies: string[];
    names: Intl.DisplayNames;
    disabled: boolean;
    onChange: (currency: string) => void;
}) {
    return (
        <div className="grid min-w-0 gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Select value={value} onValueChange={onChange} disabled={disabled}>
                <SelectTrigger id={id} className="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
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
    const formatDate = new Intl.DateTimeFormat(locale, {
        dateStyle: 'medium',
        timeZone: 'Asia/Phnom_Penh',
    });
    const form = useForm({
        from: filters.from ?? '',
        to: filters.to ?? '',
    });
    const alternateLanguage = language === 'km' ? 'en' : 'km';
    const pageLink = (page: number, lang: Language = language) =>
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
        <div
            lang={language}
            className="min-h-screen bg-muted/35 text-foreground"
        >
            <Head title={copy.title} />
            <header className="border-b bg-background">
                <nav className="mx-auto flex max-w-4xl items-center justify-between gap-4 px-5 py-3 sm:px-8">
                    <Button asChild variant="ghost" size="sm">
                        <Link href={home()}>
                            <ArrowLeft aria-hidden="true" />
                            {copy.home}
                        </Link>
                    </Button>
                    <Button asChild variant="outline" size="sm">
                        <Link
                            href={pageLink(pagination.page, alternateLanguage)}
                            preserveState
                            hrefLang={alternateLanguage}
                            lang={alternateLanguage}
                        >
                            {alternateLanguage === 'km' ? 'ខ្មែរ' : 'English'}
                        </Link>
                    </Button>
                </nav>
            </header>

            <main className="mx-auto grid max-w-4xl gap-6 px-5 py-8 sm:px-8 sm:py-12">
                <div>
                    <h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">
                        {copy.title}
                    </h1>
                    <p className="mt-2 text-muted-foreground">
                        {copy.description}
                    </p>
                </div>

                <Card>
                    <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-2">
                        <CardTitle>{copy.converter}</CardTitle>
                        {meta.is_stale && (
                            <Badge variant="secondary">{copy.stale}</Badge>
                        )}
                    </CardHeader>
                    <CardContent className="grid gap-5">
                        <div className="grid items-end gap-3 sm:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)_auto_minmax(0,1.2fr)]">
                            <div className="grid gap-2">
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
                                label={copy.sourceCurrency}
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
                                label={copy.targetCurrency}
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
                            className="grid gap-1 rounded-lg border bg-muted/50 p-4 sm:p-5"
                        >
                            <p className="text-sm font-medium text-muted-foreground">
                                {copy.result}
                            </p>
                            <p className="text-2xl font-semibold tracking-tight break-words tabular-nums sm:text-3xl">
                                {resultText}
                            </p>
                            {latest && !updatingPair && (
                                <p className="text-sm text-muted-foreground">
                                    1 {pair.base} ={' '}
                                    {formatRate.format(Number(latest.rate))}{' '}
                                    {pair.quote}
                                    {' · '}
                                    {copy.effective}{' '}
                                    <time dateTime={latest.effective_date}>
                                        {formatDate.format(
                                            new Date(latest.effective_date),
                                        )}
                                    </time>
                                </p>
                            )}
                        </div>

                        {latest &&
                            pair.base !== 'KHR' &&
                            pair.quote !== 'KHR' && (
                                <p className="text-sm text-muted-foreground">
                                    {copy.crossRate}
                                </p>
                            )}
                        {meta.sync_failed && !sameCurrency && (
                            <p
                                role="status"
                                className="rounded-md border bg-muted px-3 py-2 text-sm"
                            >
                                {copy.failed}
                            </p>
                        )}
                        {!sameCurrency && (
                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                <span>
                                    {language === 'km'
                                        ? copy.publisher
                                        : meta.original_publisher}
                                </span>
                                <a
                                    href={meta.source_url}
                                    className="inline-flex items-center gap-1 font-medium underline underline-offset-4"
                                >
                                    {copy.source}{' '}
                                    <ArrowUpRight
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                </a>
                                {meta.last_synced_at && (
                                    <span className="text-muted-foreground">
                                        {copy.synced}{' '}
                                        <time dateTime={meta.last_synced_at}>
                                            {formatDate.format(
                                                new Date(meta.last_synced_at),
                                            )}
                                        </time>
                                    </span>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {!sameCurrency && (
                    <>
                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    {copy.chart} · {pairLabel}
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    {copy.window}
                                </p>
                            </CardHeader>
                            <CardContent
                                className="grid gap-5"
                                aria-busy={form.processing || updatingPair}
                            >
                                <form
                                    onSubmit={filterHistory}
                                    className="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
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
                                        disabled={
                                            form.processing || updatingPair
                                        }
                                    >
                                        {form.processing
                                            ? copy.loading
                                            : copy.apply}
                                    </Button>
                                    <InputError
                                        role="alert"
                                        message={
                                            form.errors.from || form.errors.to
                                                ? copy.invalidDates
                                                : undefined
                                        }
                                        className="sm:col-span-3"
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
                                        formatDate={formatDate}
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

                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    {copy.history} · {pairLabel}
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    {copy.historyDescription}
                                </p>
                                {archiveUrl && (
                                    <a
                                        href={archiveUrl}
                                        className="text-sm underline underline-offset-4"
                                    >
                                        {copy.archive}
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
                                    <table className="w-full text-sm">
                                        <caption className="sr-only">
                                            {copy.history}
                                        </caption>
                                        <thead className="border-b text-left text-muted-foreground">
                                            <tr>
                                                <th
                                                    scope="col"
                                                    className="py-2 font-medium"
                                                >
                                                    {copy.effective}
                                                </th>
                                                <th
                                                    scope="col"
                                                    className="py-2 text-right font-medium"
                                                >
                                                    {rateLabel}
                                                </th>
                                                <th
                                                    scope="col"
                                                    className="py-2 pl-4 text-right font-medium"
                                                >
                                                    {copy.provider}
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
                                                            {formatDate.format(
                                                                new Date(
                                                                    entry.effective_date,
                                                                ),
                                                            )}
                                                        </time>
                                                    </td>
                                                    <td className="py-3 text-right tabular-nums">
                                                        {formatRate.format(
                                                            Number(entry.rate) /
                                                                entry.unit,
                                                        )}{' '}
                                                        {entry.quote_currency}
                                                    </td>
                                                    <td className="py-3 pl-4 text-right text-muted-foreground">
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
                <footer className="text-sm text-muted-foreground">
                    {copy.disclaimer}
                </footer>
            </main>
        </div>
    );
}
