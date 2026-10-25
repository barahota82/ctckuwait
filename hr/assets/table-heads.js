/* Demo V17: visual sticky heads outside horizontal scroll containers.
 * The real table, its semantics, data, columns and scrolling remain intact. */
(() => {
  'use strict';
  const selector = '#page .record-table187 table.record-table191, #page .finance-workspace190 .finance-results190 .table-wrap table';
  const entries = new Map();
  let frame = 0, rescan = true;
  const styleKeys = ['font-family','font-size','font-weight','font-style','line-height','letter-spacing','color','background-color','text-align','text-transform','white-space','word-break','overflow-wrap','direction','padding-top','padding-right','padding-bottom','padding-left','border-top','border-right','border-bottom','border-left'];
  const queue = (scan = false) => { rescan ||= scan; if (!frame) frame = requestAnimationFrame(update); };
  const resize = new ResizeObserver(() => { for (const entry of entries.values()) entry.dirty = true; queue(); });
  function remove(table, entry) { resize.unobserve(table); resize.unobserve(entry.wrap); entry.overlay.remove(); entries.delete(table); }
  function scan() {
    const tables = new Set(document.querySelectorAll(selector));
    for (const [table, entry] of entries) if (!tables.has(table)) remove(table, entry);
    for (const table of tables) {
      if (entries.has(table)) { entries.get(table).dirty = true; continue; }
      const wrap = table.closest('.table-wrap');
      const overlay = document.createElement('div');
      overlay.className = 'table-head192'; overlay.hidden = true;
      overlay.setAttribute('aria-hidden', 'true'); overlay.setAttribute('inert', '');
      document.body.append(overlay);
      entries.set(table, {wrap, overlay, dirty: true, cells: []});
      resize.observe(table); resize.observe(wrap);
    }
  }
  function topEdge(table, wrapRect) {
    let top = 0;
    const workspace = table.closest('.workspace') || document;
    for (const bar of workspace.querySelectorAll('.topbar, #portalTabs32, #recordTabs49, .employee-editor-sections134')) {
      const style = getComputedStyle(bar), rect = bar.getBoundingClientRect();
      if (!['fixed','sticky'].includes(style.position) || style.top === 'auto' || !rect.height) continue;
      if (rect.top <= parseFloat(style.top) + 1 && rect.bottom > 0 && rect.right > wrapRect.left && rect.left < wrapRect.right) top = Math.max(top, rect.bottom);
    }
    return top;
  }
  function rebuild(entry, head) {
    entry.overlay.replaceChildren(); entry.cells = [];
    for (const original of head.querySelectorAll('th,td')) {
      const cell = document.createElement('div'), text = document.createElement('span');
      const style = getComputedStyle(original);
      cell.className = 'table-head-cell192';
      for (const key of styleKeys) cell.style.setProperty(key, style.getPropertyValue(key));
      text.textContent = original.textContent; cell.append(text); entry.overlay.append(cell);
      entry.cells.push({original, cell});
    }
    entry.dirty = false;
  }
  function update() {
    frame = 0;
    if (rescan) { rescan = false; scan(); }
    for (const [table, entry] of entries) {
      const head = table.tHead, wrapRect = entry.wrap.getBoundingClientRect();
      if (!head || !table.tBodies[0]?.rows.length) { entry.overlay.hidden = true; continue; }
      const headRect = head.getBoundingClientRect(), tableRect = table.getBoundingClientRect(), headStyle = getComputedStyle(head);
      // RTL browsers may reserve the document scrollbar on the left. Fixed
      // coordinates start inside that gutter; DOM rectangles include it.
      const gutter = Math.max(0, document.documentElement.getBoundingClientRect().left + window.scrollX);
      const top = topEdge(table, wrapRect), left = Math.max(gutter, wrapRect.left + entry.wrap.clientLeft), right = Math.min(gutter + document.documentElement.clientWidth, wrapRect.left + entry.wrap.clientLeft + entry.wrap.clientWidth);
      // The finance screens retain their existing stacked mobile layout (no visible table head).
      if (headStyle.display === 'none' || headStyle.visibility === 'hidden' || headRect.height < 5 || headRect.width < 5 || headRect.top >= top || tableRect.bottom <= top || right <= left || document.querySelector('dialog[open]')) {
        entry.overlay.hidden = true; continue;
      }
      if (entry.dirty) rebuild(entry, head);
      const height = Math.min(headRect.height, tableRect.bottom - top);
      Object.assign(entry.overlay.style, {left: (left-gutter)+'px', top: top+'px', width: (right-left)+'px', height: height+'px'});
      for (const {original, cell} of entry.cells) {
        const rect = original.getBoundingClientRect();
        Object.assign(cell.style, {left: (rect.left-left)+'px', top: (rect.top-headRect.top)+'px', width: rect.width+'px', height: rect.height+'px'});
      }
      entry.overlay.hidden = false;
    }
  }
  document.addEventListener('scroll', () => queue(), {passive:true, capture:true});
  window.addEventListener('resize', () => { for (const entry of entries.values()) entry.dirty = true; queue(); }, {passive:true});
  document.addEventListener('close', () => queue(), true);
  new MutationObserver(records => {
    if (records.some(record => !record.target.closest?.('.table-head192'))) queue(true);
  }).observe(document.getElementById('app'), {childList:true, subtree:true, characterData:true});
  new MutationObserver(() => queue()).observe(document.getElementById('modal'), {attributes:true, attributeFilter:['open']});
  document.fonts?.ready.then(() => { for (const entry of entries.values()) entry.dirty = true; queue(); });
  queue(true);
})();
