export async function exportAsPDF(svgData, title, clientWidth, clientHeight) {

    try {


        // const svgData = new XMLSerializer().serializeToString(
        //     svgElement
        // );

        const styles = [...document.styleSheets]
            .map((sheet) => {
                try {
                    return [...sheet.cssRules]
                        .map((rule) => rule.cssText)
                        .join("");
                } catch (e) {
                    return "";
                }
            })
            .join("");

        const printWindow = window.open("", "_blank");
        if (!printWindow) {
            throw new Error("Error opening the print window.");
        }

        let printStyles = `
            body, html {
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .print-message, .button-container, .loading-message, .safari-print-instruction {
                position: fixed;
                left: 50%;
                transform: translateX(-50%);
            }
            .loading-message {
                top: 50px;
                font-size: 16px;
                color: #555;
                font-family: Arial, sans-serif;
                text-align: center;
            }
            .print-message {
                top: 20px;
                font-size: 14px;
                color: #555;
                font-family: Arial, sans-serif;
                text-align: center;
            }
            .button-container {
                top: 50px;
                display: none;
                gap: 10px;
            }
            .button-container button {
                padding: 10px 20px;
                font-size: 16px;
                color: #fff;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                transition: background-color 0.3s;
            }
            #printButton {
                background-color: #007bff;
            }
            #printButton:hover {
                background-color: #0056b3;
            }
            #closeButton {
                background-color: #6c757d;
            }
            #closeButton:hover {
                background-color: #5a6268;
            }
            .safari-print-instruction {
                display: none;
                top: 40px;
                font-size: 14px;
                color: #333;
                font-family: Arial, sans-serif;
                text-align: center;
            }
            @media print {
                body, html {
                    overflow: hidden;
                    page-break-before: always;
                    page-break-inside: avoid;
                    page-break-after: always;
                }
                .print-message, .button-container, .loading-message, .safari-print-instruction {
                    display: none;
                }
            }`;

        const isSafari = /^((?!chrome|android).)*safari/i.test(
            navigator.userAgent
        );
        const isLandscape = true

        if (isLandscape) {
            const svgWidth = clientWidth || "16.54in";
            const svgHeight = clientHeight || "8in";


            printStyles += `
                .print-container {
                    width: ${svgWidth};
                    height: ${svgHeight};
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    overflow: hidden;
                    position: relative;
                }`;

            if (!isSafari) {
                printStyles += `@page {
                    size: ${svgWidth} ${svgHeight};
                    margin: 0;
                }`;
            }
        } else {
            const pageOrientation = isLandscape
                ? "landscape"
                : "portrait";
            printStyles += `@page {
                size: ${pageOrientation};
                margin: 0;
            }`;
        }

        printWindow.document.write(`
            <html>
                <head>
                    <title>${title}-${Date.now()}</title>
                    <style>
                        ${styles}
                        ${printStyles}
                    </style>
                </head>
                <body>
                    <div class="print-container">
                        ${svgData}
                        <p class="loading-message">Loading...</p>
                        <p class="print-message">
                            Please adjust the orientation and size if necessary to ensure the image prints correctly.
                        </p>
                        <div class="button-container" style="display: none;">
                            <button id="printButton">Print</button>
                            <button id="closeButton">Close</button>
                        </div>
                        <p class="safari-print-instruction" style="display: ${
                            isSafari ? "block" : "none"
                        };">
                            For Safari, please use Cmd + P to print.
                        </p>
                    </div>
                </body>
            </html>
        `);

        await waitForImagesToLoad(printWindow.document);

        const loadingMessage =
            printWindow.document.querySelector(".loading-message");
        loadingMessage.style.display = "none";

        const buttonContainer =
            printWindow.document.querySelector(".button-container");
        const safariInstruction = printWindow.document.querySelector(
            ".safari-print-instruction"
        );

        if (isSafari) {
            safariInstruction.style.display = "block";
            buttonContainer.style.display = "none";
        } else {
            buttonContainer.style.display = "flex";
        }

        if (!isSafari) {
            const printButton =
                printWindow.document.getElementById("printButton");
            printButton.addEventListener("click", () => {
                buttonContainer.style.display = "none";
                printWindow.print();
            });
        }

        const closeButton =
            printWindow.document.getElementById("closeButton");
        closeButton.addEventListener("click", () => {
            printWindow.close();
        });

        if (!isSafari) {
            printWindow.onafterprint = () => {
                buttonContainer.style.display = "flex";
            };
        }
    } catch (error) {
        console.error(
            "There was an error while generating the PDF File:",
            error
        );
    }
}



export function waitForImagesToLoad(doc) {
    const images = Array.from(doc.querySelectorAll("image"));

    return Promise.all(
        images.map((img) => {
            return new Promise((resolve) => {
                const href =
                    img.getAttributeNS(
                        "http://www.w3.org/1999/xlink",
                        "href"
                    ) || img.getAttribute("href");

                if (!href) {
                    resolve();
                    return;
                }

                const testImg = new Image();
                testImg.src = href;

                testImg.onload = () => {
                    resolve();
                };

                testImg.onerror = () => {
                    console.warn("Error loading image:", href);
                    resolve();
                };
            });
        })
    );
}


export function exportAsSVG(svgData, title){

    // const svgData = new XMLSerializer().serializeToString(svg)
    const blob = new Blob([svgData], { 'type': 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = title + '.svg'
    link.click()
    URL.revokeObjectURL(url)
}

export function exportAsPNG(svgData, title, clientWidth, clientHeight){

    // const node = this.$refs.violinPlotContainer.querySelector('svg')
    // const svgData = new XMLSerializer().serializeToString(svg)
    const canvas = document.createElement('canvas');
    canvas.width = clientWidth
    canvas.height = clientHeight
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = 'white';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    // const v = Canvg.fromString(ctx, svgData);
    // v.render();
    const DOMURL = window.URL || window.webkitURL || window;
    const img = new Image();
    const svgBlob = new Blob([svgData], {
        type: "image/svg+xml;charset=utf-8",
    });
    const url = DOMURL.createObjectURL(svgBlob);
    img.onload = () => {
        ctx.drawImage(img, 0, 0);
        DOMURL.revokeObjectURL(url);
        console.log('testing')
        canvas.toBlob((blob) => {
                const link = document.createElement('a');
                link.download = title + '.png';
                link.href = DOMURL.createObjectURL(blob);
                link.click();
        }, 'image/png');

    }
    img.src = url;

}
