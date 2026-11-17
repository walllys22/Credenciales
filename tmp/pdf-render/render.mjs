import fs from 'node:fs';
import {createCanvas,DOMMatrix,ImageData,Path2D} from './node_modules/@napi-rs/canvas/index.js';
Object.assign(globalThis,{DOMMatrix,ImageData,Path2D});
const {getDocument}=await import('./node_modules/pdfjs-dist/legacy/build/pdf.mjs');
const pdf=await getDocument({data:new Uint8Array(fs.readFileSync('tmp/pdfs/reporte-qa.pdf')),disableFontFace:true,useSystemFonts:true}).promise;
for(let i=1;i<=pdf.numPages;i++){
 const page=await pdf.getPage(i),v=page.getViewport({scale:1.3333}),c=createCanvas(Math.ceil(v.width),Math.ceil(v.height));
 await page.render({canvasContext:c.getContext('2d'),viewport:v}).promise;
 fs.writeFileSync(`tmp/pdfs/pdfjs-${i}.png`,c.toBuffer('image/png'));
}
console.log('Rendered',pdf.numPages,'pages using PDF.js');
