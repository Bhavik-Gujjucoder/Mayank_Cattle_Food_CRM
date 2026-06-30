/** Strip leading slash so paths resolve against Playwright baseURL (subdir installs). */
export function appPath(path: string): string {
    return path.replace(/^\/+/, '');
}
