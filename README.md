# Commission Calculator

[![Tests](https://github.com/RumenDamyanov/commission-calculator/actions/workflows/workflow.yml/badge.svg)](https://github.com/RumenDamyanov/commission-calculator/actions)
[![codecov](https://codecov.io/gh/RumenDamyanov/commission-calculator/graph/badge.svg?token=UBA951GY24)](https://codecov.io/gh/RumenDamyanov/commission-calculator)

## Overview

Commission Calculator is a development tool designed to compute commission rates for various business scenarios. It provides a flexible and configurable system for calculating commissions with customizable rates.

## Configuration

The application is configured using YAML files located in the `config` directory. There is example `.env` file that could be used as a starting point.

## Usage

Configure the desired commission rates in the configuration file and use the calculator service to compute commissions based on your business rules.

## Development

The application is currently in development mode. To get started:

1. Clone the repository.
2. Configure your settings in `config/services.yml`.
3. Copy `.env-example` to `.env` and configure your API keys (not included). Beware of rate limits!
4. Run the application `./bin/calculate data/input.txt` (or use your own input file).

```bash
# Using default output location (data/output.txt)
./bin/calculate input.txt

# Using custom output location
./bin/calculate input.txt /custom/path/output.txt
```

### Notes

- The application is designed to be run in a terminal.
- You need your own API keys for the services used.
- Beware of rate limits! Some free services have very low limits (e.g. 5 requests per hour).

## Testing

To run tests locally:

```bash
# Run tests with coverage
composer test

# View coverage report
open tests/coverage/index.html
```

## Code Coverage

The project maintains 100% code coverage. Coverage reports are:

- Generated automatically on each push
- Available in the GitHub Actions artifacts
- Published to [Codecov](https://codecov.io/gh/RumenDamyanov/commission-calculator)

## License

MIT License. See [LICENSE](LICENSE) for more details.
