<img src=".github/art/logo.svg" alt="" width="64">

# Simplify Plugins

A [Pelican](https://pelican.dev) plugin that shows the themes, plugins and languages on the
[Pelican Hub](https://hub.pelican.dev) inside your panel, with their makers up front, and installs them from the Hub.

- Every item shows who made it, and who it is based on when the maker says so.
- Installs and updates come from the Pelican Hub only. This plugin has no other download source, no zip upload and no
  updater of its own: managing installed plugins stays on Pelican's own Plugins page.

It works on its own and is also an add-on for [Simplify](https://github.com/jerajerajerajera/simplify).

## Set up

1. Install the plugin from the [Pelican Hub](https://hub.pelican.dev).
2. In the admin area, open **Plugins → Browse Hub**.

## With Simplify

When [Simplify](https://github.com/jerajerajerajera/simplify) is installed too, Simplify Plugins shows up in Simplify's
add-on list and adds a **Browse Hub** button to the dashboard for root admins. Nothing else changes; there is no hard
dependency either way.

## What it sends where

Only the Pelican Hub (`hub.pelican.dev`): the public list of plugins and themes, and the plugin files when you press
Install. Downloads go through Pelican's own plugin service, which checks the file against the Hub's checksum.

## License

Source available, see [LICENSE.md](LICENSE.md). Copyright (c) 2026 Jeroen (jerajerajerajera).
