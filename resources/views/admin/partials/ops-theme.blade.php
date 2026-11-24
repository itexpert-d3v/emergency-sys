@extends('layouts.admin')

{{--
    Intentionally empty.

    All admin styling (tokens, sidebar, cards, buttons, badges, forms, chat, modal)
    now lives in resources/css/app.css, which is the single source of truth.
    Do not add colour values or component CSS here. Page-specific LAYOUT rules may
    live in the page's own @section('styles'), but they must read the tokens
    (var(--surface-card), var(--text), var(--accent), var(--status-*), ...).
--}}