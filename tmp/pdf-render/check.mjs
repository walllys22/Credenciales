import fs from 'node:fs';
import {createCanvas,DOMMatrix,ImageData,Path2D,loadImage} from './node_modules/@napi-rs/canvas/index.js';
Object.assign(globalThis,{DOMMatrix,ImageData,Path2D});
const {getDocument}=await import('./node_modules/pdfjs-dist/legacy/build/pdf.mjs');
const pdf=await getDocument({data:new Uint8Array(fs.readFileSync('tmp/pdfs/reporte-qa.pdf'))}).promise;
for(let i=1;i<=pdf.numPages;i++){
 const page=await pdf.getPage(i),text=(await page.getTextContent()).items.map(x=>x.str).join(' ');
 const numbers=(await page.getTextContent()).items.filter(x=>/^\d+$/.test(x.str)&&x.transform[4]<40&&x.transform[5]<650&&x.transform[5]>60).map(x=>Number(x.str));
 console.log(JSON.stringify({page:i,title:text.includes('Listado de Inscritos'),footer:text.includes('BOLIVIA 2026'),pageNumber:text.includes(`Página ${i} de ${pdf.numPages}`),rows:numbers}));
 const image=await loadImage(`tmp/pdfs/pdfjs-${i}.png`),canvas=createCanvas(816,220);canvas.getContext('2d').drawImage(image,0,-836);fs.writeFileSync(`tmp/pdfs/footer-check-${i}.png`,canvas.toBuffer('image/png'));
}
