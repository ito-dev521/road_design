# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a minimal web development directory containing only basic files. The repository appears to be in the early stages of development or used for testing purposes.

## Current Structure

- `test.html` - An empty HTML file (0 bytes)
- `setup-claude.ps1` - PowerShell script to configure Claude Code environment and launch it
- `CLAUDE.md` - This file with project guidance

## Environment Setup

The repository includes a PowerShell script (`setup-claude.ps1`) that:
- Configures Node.js PATH
- Sets the Claude Code OAuth token
- Creates the `claude` command alias
- Verifies the setup is working correctly

To use the setup script:
```powershell
.\setup-claude.ps1
```

After running the setup script, you can use:
```powershell
claude
```

## Development Notes

This directory currently contains minimal files and no package.json or other configuration files, suggesting it's either:
- A new project that hasn't been initialized yet
- A testing/experimentation directory
- A workspace for web development setup

Since there are no build tools, package managers, or frameworks detected, standard web development practices would apply when adding new functionality.

## Claude Code Integration

This project is configured to work with Claude Code. The setup script ensures:
- Node.js is available in PATH
- Claude Code OAuth token is set
- `claude` command is available for interactive development