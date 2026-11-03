import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { NodePackageImporter } from 'sass';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/scss/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                // Lets the Sass load GOV.UK Frontend as "pkg:govuk-frontend".
                importers: [new NodePackageImporter()],
                quietDeps: true,
            },
        },
        // GOV.UK Frontend keeps a few old Internet Explorer media query hacks ("screen\0"), which
        // the minifier can't parse. Drop them rather than fail the build.
        lightningcss: {
            errorRecovery: true,
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
