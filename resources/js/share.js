const FOREST = '#0B3D2E';
const AMBER = '#F5A623';
const FONT = "'Exo 2', ui-sans-serif, system-ui, sans-serif";

// Closing the share sheet rejects navigator.share with an AbortError. The
// player chose that, so it is not an error worth reporting.
const share = async (data) => {
    try {
        await navigator.share(data);
    } catch (error) {
        if (error?.name !== 'AbortError') {
            throw error;
        }
    }
};

const fit = (ctx, text, maxWidth) => {
    if (ctx.measureText(text).width <= maxWidth) {
        return text;
    }

    let shortened = text;
    while (shortened.length > 1 && ctx.measureText(`${shortened}...`).width > maxWidth) {
        shortened = shortened.slice(0, -1);
    }

    return `${shortened.trimEnd()}...`;
};

const drawLogo = (ctx, x, y, size) => {
    const scale = size / 84;
    ctx.save();
    ctx.translate(x, y);
    ctx.scale(scale, scale);
    ctx.strokeStyle = 'white';
    ctx.lineWidth = 7;
    ctx.lineCap = 'round';
    ctx.beginPath();
    ctx.moveTo(46, 46);
    ctx.lineTo(68, 68);
    ctx.stroke();
    ctx.beginPath();
    ctx.arc(40, 36, 28, 0, Math.PI * 2);
    ctx.stroke();
    [[46, 46], [68, 68]].forEach(([cx, cy]) => {
        ctx.fillStyle = AMBER;
        ctx.beginPath();
        ctx.arc(cx, cy, 6.5, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = FOREST;
        ctx.beginPath();
        ctx.arc(cx, cy, 2.5, 0, Math.PI * 2);
        ctx.fill();
    });
    ctx.restore();
};

export const resultImage = async ({ quest, score, rank, scoreLabel, url }) => {
    // Faces load lazily, so ask for the ones the image draws with.
    await Promise.all([
        document.fonts?.load(`600 34px ${FONT}`),
        document.fonts?.load(`800 64px ${FONT}`),
    ]);

    const canvas = document.createElement('canvas');
    canvas.width = 1080;
    canvas.height = 1350;
    const ctx = canvas.getContext('2d');
    ctx.textAlign = 'center';

    ctx.fillStyle = FOREST;
    ctx.fillRect(0, 0, 1080, 1350);
    ctx.strokeStyle = 'rgba(245, 166, 35, 0.12)';
    ctx.lineWidth = 90;
    ctx.beginPath();
    ctx.arc(1000, 80, 260, 0, Math.PI * 2);
    ctx.stroke();

    drawLogo(ctx, 440, 110, 200);

    ctx.fillStyle = '#FFFFFF';
    ctx.font = `800 64px ${FONT}`;
    ctx.fillText(fit(ctx, quest, 900), 540, 460);

    ctx.fillStyle = '#FFFFFF';
    ctx.beginPath();
    ctx.roundRect(120, 560, 840, 420, 40);
    ctx.fill();

    ctx.fillStyle = '#6B7280';
    ctx.font = `600 34px ${FONT}`;
    ctx.fillText(scoreLabel.toUpperCase(), 540, 650);
    ctx.fillStyle = FOREST;
    ctx.font = `800 200px ${FONT}`;
    ctx.fillText(score, 540, 840);
    if (rank) {
        ctx.fillStyle = '#6B7280';
        ctx.font = `600 40px ${FONT}`;
        ctx.fillText(rank, 540, 925);
    }

    ctx.fillStyle = AMBER;
    ctx.font = `800 54px ${FONT}`;
    ctx.fillText(url.replace(/^https?:\/\//, ''), 540, 1180);

    return new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
};

export const result = async (detail) => {
    if (!navigator.share) {
        return;
    }

    const data = { title: detail.title, text: detail.text, url: detail.url };

    try {
        const blob = await resultImage(detail);
        const file = new File([blob], 'questify-result.png', { type: 'image/png' });

        if (navigator.canShare?.({ files: [file] })) {
            data.files = [file];
            data.text = `${data.text} ${data.url}`;
            delete data.url;
        }
    } catch (error) {
        window.Sentry?.captureException(error);
    }

    await share(data);
};

export const link = async (url) => {
    if (navigator.share) {
        await share({ title: 'Join Quest', url });
    } else {
        await navigator.clipboard.writeText(url);
    }
};
