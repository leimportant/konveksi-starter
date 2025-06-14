// src/lib/truncateHtml.ts
export function truncateHtml(html: string, maxLength: number): string {
  const div = document.createElement('div')
  div.innerHTML = html || ''
  const text = div.textContent || div.innerText || ''
  return text.length > maxLength ? text.slice(0, maxLength) + '...' : text
}
