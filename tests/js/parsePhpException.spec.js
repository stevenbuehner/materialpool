import {describe, expect, it} from 'vitest';
import {parsePhpException} from '../../resources/js/apps/main/pages/parsePhpException';

describe('PHP exception presentation', () => {
  it('separates the message and application frames from the complete trace', () => {
    const result = parsePhpException('RuntimeException: Failed in /var/www/app/Jobs/Example.php:20\nStack trace:\n#0 /var/www/vendor/laravel/framework/src/Worker.php(12): run()\n#1 /var/www/app/Jobs/Example.php(20): handle()\n#2 {main}');

    expect(result.headline).toBe('RuntimeException: Failed in /var/www/app/Jobs/Example.php:20');
    expect(result.applicationFrames).toEqual(['#1 /var/www/app/Jobs/Example.php(20): handle()']);
    expect(result.trace).toContain('#0 /var/www/vendor/laravel/framework/src/Worker.php');
  });

  it('keeps a short error readable without a stack trace', () => {
    expect(parsePhpException('synthetic-exception')).toEqual({headline: 'synthetic-exception', applicationFrames: [], trace: ''});
  });
});
