export function parsePhpException(exception) {
  const lines = (exception || '').split(/\r?\n/);
  const frames = lines.filter(line => /^#\d+\s/.test(line));
  return {
    headline: lines[0] || '',
    applicationFrames: frames.filter(line => /\/(?:app|routes)\//.test(line)).slice(0, 5),
    trace: lines.slice(1).join('\n').trim(),
  };
}
