import { Link, usePage } from '@inertiajs/react';
import { ArrowDown, ArrowLeftRight, ArrowRight, MapPinned } from 'lucide-react';
import { publicBrand } from '@/components/app-logo';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import PublicLayout, { LanguageText } from '@/layouts/public-layout';
import type { PublicLanguage } from '@/layouts/public-layout';
import { home } from '@/routes';
import { index as administrativeAreas } from '@/routes/administrative-areas';
import { index as exchangeRates } from '@/routes/exchange-rates';

const translations = {
    en: {
        home: 'Home',
        title: 'Public data for everyday life in Cambodia.',
        description: `${publicBrand} makes useful Cambodian data easy to find and use in Khmer and English.`,
        explore: 'Explore the data',
        rates: {
            title: 'Exchange rates',
            description:
                'Convert between currencies and follow daily exchange-rate history for the Cambodian riel and other currencies.',
            action: 'Open currency converter',
        },
        areas: {
            title: 'Administrative areas',
            description:
                'Look up the names and codes of Cambodian provinces, districts, communes, and villages.',
            action: 'Browse the directory',
        },
    },
    km: {
        home: 'ទំព័រដើម',
        title: 'ទិន្នន័យសាធារណៈសម្រាប់ជីវិតប្រចាំថ្ងៃនៅកម្ពុជា។',
        description: `${publicBrand} ជួយឱ្យអ្នកងាយស្រួលស្វែងរក និងប្រើប្រាស់ទិន្នន័យមានប្រយោជន៍អំពីកម្ពុជា ជាភាសាខ្មែរ និងអង់គ្លេស។`,
        explore: 'មើលទិន្នន័យ',
        rates: {
            title: 'អត្រាប្តូរប្រាក់',
            description:
                'ប្តូរប្រាក់រវាងរូបិយប័ណ្ណ និងមើលប្រវត្តិអត្រាប្តូរប្រាក់ប្រចាំថ្ងៃសម្រាប់ប្រាក់រៀល និងរូបិយប័ណ្ណផ្សេងទៀត។',
            action: 'បើកកម្មវិធីគណនាអត្រាប្តូរប្រាក់',
        },
        areas: {
            title: 'តំបន់រដ្ឋបាល',
            description:
                'ស្វែងរកឈ្មោះ និងលេខកូដរាជធានី ខេត្ត ក្រុង ស្រុក ខណ្ឌ ឃុំ សង្កាត់ និងភូមិនៅកម្ពុជា។',
            action: 'មើលបញ្ជីតំបន់រដ្ឋបាល',
        },
    },
};

export default function Welcome() {
    const { url } = usePage();
    const search = new URLSearchParams(url.split('?')[1]);
    const language: PublicLanguage = search.get('lang') === 'km' ? 'km' : 'en';
    const copy = translations[language];
    const query = { lang: language };
    const datasets = [
        {
            section: 'rates',
            icon: ArrowLeftRight,
            href: exchangeRates({ query }),
        },
        {
            section: 'areas',
            icon: MapPinned,
            href: administrativeAreas({ query }),
        },
    ] as const;

    return (
        <PublicLayout
            title={copy.home}
            section="home"
            language={language}
            languageHref={home({
                query: { lang: language === 'en' ? 'km' : 'en' },
            })}
        >
            <section
                aria-labelledby="home-heading"
                className="grid overflow-hidden rounded-2xl border border-border bg-accent lg:grid-cols-2"
            >
                <div className="grid content-center justify-items-start gap-5 px-6 py-9 sm:px-10 sm:py-12 lg:px-13 lg:py-16">
                    <h1
                        id="home-heading"
                        className="max-w-xl text-4xl leading-[1.25] font-semibold tracking-tight text-balance text-foreground sm:text-5xl"
                    >
                        <LanguageText
                            language={language}
                            en={translations.en.title}
                            km={translations.km.title}
                        />
                    </h1>
                    <p className="max-w-sm text-base leading-relaxed text-muted-foreground sm:text-lg">
                        <LanguageText
                            language={language}
                            en={translations.en.description}
                            km={translations.km.description}
                        />
                    </p>
                    <Button
                        asChild
                        size="lg"
                        className="mt-1 h-auto min-h-11 rounded-lg px-4 py-2.5"
                    >
                        <a href="#datasets">
                            <LanguageText
                                language={language}
                                en={translations.en.explore}
                                km={translations.km.explore}
                            />
                            <ArrowDown aria-hidden="true" />
                        </a>
                    </Button>
                </div>
                <div className="relative aspect-video min-w-0 overflow-hidden bg-muted md:aspect-[3/1] lg:aspect-auto">
                    <img
                        src="/assets/klaing-kh-market.webp"
                        alt=""
                        fetchPriority="high"
                        width={1200}
                        height={900}
                        className="absolute inset-0 size-full object-cover"
                    />
                </div>
            </section>

            <section
                id="datasets"
                aria-labelledby="datasets-heading"
                className="grid scroll-mt-6 gap-4 py-2 sm:py-4"
            >
                <h2
                    id="datasets-heading"
                    className="text-2xl leading-tight font-semibold tracking-tight sm:text-3xl"
                >
                    <LanguageText
                        language={language}
                        en={translations.en.explore}
                        km={translations.km.explore}
                    />
                </h2>
                <div className="grid gap-4 md:grid-cols-2">
                    {datasets.map(({ section, icon: Icon, href }) => (
                        <Card
                            key={section}
                            className="gap-0 rounded-2xl p-0 shadow-none"
                        >
                            <Link
                                href={href}
                                className="group flex h-full flex-col items-start gap-3 rounded-2xl p-5 transition-colors hover:bg-muted/55 focus-visible:outline-2 focus-visible:outline-offset-2 sm:p-6"
                            >
                                <span className="grid size-11 place-items-center rounded-xl bg-accent text-accent-foreground">
                                    <Icon
                                        aria-hidden="true"
                                        className="size-5"
                                    />
                                </span>
                                <h3 className="text-xl leading-snug font-semibold tracking-tight">
                                    <LanguageText
                                        language={language}
                                        en={translations.en[section].title}
                                        km={translations.km[section].title}
                                    />
                                </h3>
                                <p className="max-w-md leading-relaxed text-muted-foreground">
                                    <LanguageText
                                        language={language}
                                        en={
                                            translations.en[section].description
                                        }
                                        km={
                                            translations.km[section].description
                                        }
                                    />
                                </p>
                                <span className="mt-auto inline-flex items-center gap-2 pt-1 text-sm font-semibold text-primary group-hover:underline group-hover:underline-offset-4">
                                    <LanguageText
                                        language={language}
                                        en={translations.en[section].action}
                                        km={translations.km[section].action}
                                    />
                                    <ArrowRight
                                        aria-hidden="true"
                                        className="size-4 shrink-0"
                                    />
                                </span>
                            </Link>
                        </Card>
                    ))}
                </div>
            </section>
        </PublicLayout>
    );
}
