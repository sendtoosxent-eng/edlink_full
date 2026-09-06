import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';
import * as FileSystem from 'expo-file-system/legacy';
const escape = (value: unknown) => String(value ?? '—').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]!);
export async function exportNativeReport(title: string, rows: Array<{ title: string; details: Record<string, unknown> }>) {
  const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><style>@page{margin:28px}body{font-family:Arial;color:#0B132B;font-size:12px}h1{font-size:24px}section{break-inside:avoid;margin:18px 0;padding:15px;border:1px solid #DDE4EF;border-radius:8px}h2{font-size:16px}table{width:100%;border-collapse:collapse}td{padding:6px;border-bottom:1px solid #EEF2F8;vertical-align:top}td:first-child{width:35%;color:#63708A}</style></head><body><h1>${escape(title)}</h1>${rows.map(row => `<section><h2>${escape(row.title)}</h2><table>${Object.entries(row.details).map(([key, value]) => `<tr><td>${escape(key)}</td><td>${escape(value)}</td></tr>`).join('')}</table></section>`).join('')}</body></html>`;
  const file = await Print.printToFileAsync({ html });
  try { if (await Sharing.isAvailableAsync()) await Sharing.shareAsync(file.uri, { mimeType: 'application/pdf', dialogTitle: title, UTI: '.pdf' }); else await Print.printAsync({ uri: file.uri }); }
  finally { await FileSystem.deleteAsync(file.uri, { idempotent: true }).catch(() => undefined); }
}
