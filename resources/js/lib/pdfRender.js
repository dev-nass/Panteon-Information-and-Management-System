import * as pdfjsLib from "pdfjs-dist";
import workerUrl from "pdfjs-dist/build/pdf.worker.min.mjs?url";

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

const STANDARD_FONT_URL = "/vendor/pdfjs/standard_fonts/";

const RENDER_SCALE = 1.5;

/**
 * Render every page of a PDF to a canvas.
 *
 * @param {ArrayBuffer} bytes
 * @returns {Promise<Array<{ pageIndex: number, canvas: HTMLCanvasElement, pdfWidth: number, pdfHeight: number, scale: number }>>}
 */
export async function renderPdfPages(bytes) {
    const loadingTask = pdfjsLib.getDocument({
        data: bytes.slice(0),
        standardFontDataUrl: STANDARD_FONT_URL,
    });
    const doc = await loadingTask.promise;

    const pages = [];
    for (let pageIndex = 0; pageIndex < doc.numPages; pageIndex++) {
        const page = await doc.getPage(pageIndex + 1);
        const viewport = page.getViewport({ scale: RENDER_SCALE });

        const canvas = document.createElement("canvas");
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);
        const ctx = canvas.getContext("2d");

        await page.render({ canvasContext: ctx, viewport }).promise;

        pages.push({
            pageIndex,
            canvas,
            pdfWidth: viewport.width / RENDER_SCALE,
            pdfHeight: viewport.height / RENDER_SCALE,
            scale: RENDER_SCALE,
        });
    }

    await loadingTask.destroy();

    return pages;
}