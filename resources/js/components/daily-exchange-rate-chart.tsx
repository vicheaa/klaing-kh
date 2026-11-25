import { useEffect, useRef, useState } from 'react';
import type { PointerEvent, ReactNode } from 'react';

export type DailyRate = {
    effective_date: string;
    rate: string;
    unit: number;
};

const leftPadding = 72;
const rightPadding = 16;

export function DailyExchangeRateChart({
    points,
    formatRate,
    renderDate,
    rateLabel,
}: {
    points: DailyRate[];
    formatRate: Intl.NumberFormat;
    renderDate: (date: string) => ReactNode;
    rateLabel: string;
}) {
    const canvas = useRef<HTMLCanvasElement>(null);
    const [activeIndex, setActiveIndex] = useState<number | null>(null);
    const activePoint = points[activeIndex ?? -1] ?? points[points.length - 1];
    const firstDate = Date.parse(points[0].effective_date);
    const elapsed =
        Date.parse(points[points.length - 1].effective_date) - firstDate;

    useEffect(() => {
        const element = canvas.current;
        const context = element?.getContext('2d');

        if (!element || !context) {
            return;
        }

        function draw() {
            if (!element || !context) {
                return;
            }

            const { width, height } = element.getBoundingClientRect();
            if (width <= leftPadding + rightPadding || height < 40) {
                return;
            }

            const scale = window.devicePixelRatio || 1;
            element.width = Math.round(width * scale);
            element.height = Math.round(height * scale);
            context.scale(scale, scale);

            const left = leftPadding;
            const right = width - rightPadding;
            const top = 14;
            const bottom = height - 16;
            const values = points.map(
                (point) => Number(point.rate) / point.unit,
            );
            const minimum = Math.min(...values);
            const maximum = Math.max(...values);
            const padding = Math.max(
                (maximum - minimum) * 0.12,
                maximum * 0.002,
            );
            const lower = Math.max(0, minimum - padding);
            const upper = maximum + padding;
            const axisFormat = new Intl.NumberFormat(
                formatRate.resolvedOptions().locale,
                {
                    maximumSignificantDigits: 7,
                },
            );
            const theme = getComputedStyle(element);
            const y = (value: number) =>
                top + ((upper - value) / (upper - lower)) * (bottom - top);
            const x = (date: string) =>
                elapsed > 0
                    ? left +
                      ((Date.parse(date) - firstDate) / elapsed) *
                          (right - left)
                    : (left + right) / 2;

            context.font = `${theme.fontWeight} 12px ${theme.fontFamily}`;
            context.textBaseline = 'middle';
            context.textAlign = 'right';
            context.fillStyle = theme.color;
            context.lineWidth = 1;

            for (const value of [lower, (lower + upper) / 2, upper]) {
                context.beginPath();
                context.strokeStyle = theme.borderColor;
                context.moveTo(left, y(value));
                context.lineTo(right, y(value));
                context.stroke();
                context.fillText(
                    axisFormat.format(value),
                    left - 8,
                    y(value),
                    left - 8,
                );
            }

            context.beginPath();
            context.strokeStyle =
                theme.getPropertyValue('--chart-1').trim() || '#007d73';
            context.lineWidth = 2.5;
            context.lineJoin = 'round';
            for (const [index, point] of points.entries()) {
                const position = x(point.effective_date);
                if (index === 0) {
                    context.moveTo(position, y(values[index]));
                } else {
                    context.lineTo(position, y(values[index]));
                }
            }
            context.stroke();

            context.fillStyle = context.strokeStyle;
            context.beginPath();
            context.arc(
                x(activePoint.effective_date),
                y(Number(activePoint.rate) / activePoint.unit),
                4,
                0,
                2 * Math.PI,
            );
            context.fill();
        }

        const observer = new ResizeObserver(draw);
        observer.observe(element);
        document.fonts.addEventListener('loadingdone', draw);
        draw();

        return () => {
            observer.disconnect();
            document.fonts.removeEventListener('loadingdone', draw);
        };
    }, [activePoint, elapsed, firstDate, formatRate, points]);

    function showNearestPoint(event: PointerEvent<HTMLCanvasElement>) {
        if (elapsed === 0) {
            return;
        }

        const bounds = event.currentTarget.getBoundingClientRect();
        const fraction =
            (event.clientX - bounds.left - leftPadding) /
            Math.max(1, bounds.width - leftPadding - rightPadding);
        const selectedDay = firstDate + fraction * elapsed;
        let nearest = 0;

        for (const [index, point] of points.entries()) {
            if (
                Math.abs(Date.parse(point.effective_date) - selectedDay) <
                Math.abs(
                    Date.parse(points[nearest].effective_date) - selectedDay,
                )
            ) {
                nearest = index;
            }
        }

        setActiveIndex(nearest);
    }

    return (
        <figure className="grid gap-3 rounded-md border p-4">
            <figcaption className="flex flex-wrap items-center justify-between gap-2 text-sm">
                <span className="font-medium tabular-nums">
                    {formatRate.format(
                        Number(activePoint.rate) / activePoint.unit,
                    )}{' '}
                    {rateLabel}
                </span>
                <time
                    dateTime={activePoint.effective_date}
                    className="text-muted-foreground"
                >
                    {renderDate(activePoint.effective_date)}
                </time>
            </figcaption>
            <canvas
                ref={canvas}
                aria-hidden="true"
                className="h-56 w-full border-border text-muted-foreground"
                onPointerMove={showNearestPoint}
                onPointerLeave={() => setActiveIndex(null)}
            />
            {elapsed > 0 && (
                <div className="ml-[72px] flex justify-between text-xs text-muted-foreground">
                    <time dateTime={points[0].effective_date}>
                        {renderDate(points[0].effective_date)}
                    </time>
                    <time dateTime={points[points.length - 1].effective_date}>
                        {renderDate(points[points.length - 1].effective_date)}
                    </time>
                </div>
            )}
        </figure>
    );
}
