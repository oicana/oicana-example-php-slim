# Oicana Example PHP

Small example PHP web service that uses Oicana for PDF templating.

## Getting Started

1. Install dependencies: `composer install`
2. Follow the Oicana installer output to enable the native extension:
   - The installer will attempt to download the extension automatically
   - You need to extend the value of `PHP_INI_SCAN_DIR` as shown in the installer output
   - Verify the extenaion is loaded: `php -m | grep oicana`
3. [Download the RoadRunner binary](https://docs.roadrunner.dev/docs/general/install) and place it in your `PATH` (or in the project root)
4. Start the service: `rr serve`
5. Visit http://127.0.0.1:3004 for the **Swagger UI documentation**

The service uses [RoadRunner](https://roadrunner.dev/) as a long-running PHP application server. Templates are compiled once at startup and kept in memory across all requests.

## API Endpoints

- `GET /` - API documentation
- `GET /templates` - List all available templates
- `POST /templates/{id}/compile` - Compile template to PDF
- `POST /templates/{id}/preview` - Preview template as PNG
- `POST /templates/{id}/reset` - Reset template cache
- `GET /templates/{id}` - Download template ZIP file
- `POST /blobs` - Upload a blob (image, file, etc.)
- `POST /certificates` - Create a certificate PDF

## Licensing

The code of this example project is licensed under the [MIT license](LICENSE).

But please be aware, that the dependency `oicana` [is licensed under PolyForm Noncommercial License 1.0.0][oicana-license] and requires a commercial License for use cases that are not covered by the PolyForm License. Visit [the Oicana website][oicana-website] for pricing options.


[oicana-license]: https://github.com/oicana/oicana?tab=readme-ov-file#licensing
[oicana-website]: https://oicana.com
