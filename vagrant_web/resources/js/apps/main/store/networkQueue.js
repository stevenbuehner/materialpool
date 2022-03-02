import PQueue from 'p-queue/dist';

export const MAX_SIMULTANEOUS_DOWNLOADS = 4;

export const queue = new PQueue({
	concurrency: MAX_SIMULTANEOUS_DOWNLOADS
});

