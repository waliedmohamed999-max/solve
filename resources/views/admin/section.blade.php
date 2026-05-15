@extends('layouts.admin')

@section('title', 'Solve Admin | ' . $pageTitle)

@section('admin-content')
<div x-data="storeWizard()">
@php
    $isStoreWizard = $sectionKey === 'stores';
    $workspaceSectionKeys = ['payments', 'shipping', 'analytics', 'partners', 'support', 'apps', 'settings'];
    $isWorkspaceSection = in_array($sectionKey, $workspaceSectionKeys, true);
    $isPaymentsWorkspace = $sectionKey === 'payments';
    $usesWizard = in_array($sectionKey, ['stores', 'subscriptions'], true);
    $groupedFields = collect($form['fields'])->groupBy('group');
    $wizardGroups = collect($form['groups'] ?? [])->map(function (array $group, string $key) use ($groupedFields) {
        return [
            'key' => $key,
            'label' => $group['label'],
            'description' => $group['description'] ?? '',
            'fields' => $groupedFields->get($key, collect()),
        ];
    })->filter(fn (array $group) => $group['fields']->isNotEmpty())->values();
    $formAction = $form['editingRecord'] ? $form['editAction'] : $form['createAction'];
    $txt = [
        'export' => json_decode('"\u062a\u0635\u062f\u064a\u0631 CSV"'),
        'new_store' => json_decode('"\u0625\u0646\u0634\u0627\u0621 \u0645\u062a\u062c\u0631 \u062c\u062f\u064a\u062f"'),
        'form_error' => json_decode('"\u064a\u0648\u062c\u062f \u062e\u0637\u0623 \u0641\u064a \u0628\u0639\u0636 \u0627\u0644\u062d\u0642\u0648\u0644. \u0631\u0627\u062c\u0639 \u0627\u0644\u0628\u064a\u0627\u0646\u0627\u062a \u062b\u0645 \u0623\u0639\u062f \u0627\u0644\u0645\u062d\u0627\u0648\u0644\u0629."'),
        'search' => json_decode('"\u0627\u0644\u0628\u062d\u062b"'),
        'status' => json_decode('"\u0627\u0644\u062d\u0627\u0644\u0629"'),
        'apply' => json_decode('"\u062a\u0637\u0628\u064a\u0642"'),
        'edit_record' => json_decode('"\u062a\u0639\u062f\u064a\u0644 \u0627\u0644\u0633\u062c\u0644"'),
        'create_record' => json_decode('"\u0625\u0636\u0627\u0641\u0629 \u0633\u062c\u0644 \u062c\u062f\u064a\u062f"'),
        'wizard_help' => json_decode('"\u0623\u0643\u0645\u0644 \u0643\u0644 \u0642\u0633\u0645 \u062b\u0645 \u0627\u0646\u062a\u0642\u0644 \u0645\u0628\u0627\u0634\u0631\u0629 \u0625\u0644\u0649 \u0627\u0644\u0642\u0633\u0645 \u0627\u0644\u062a\u0627\u0644\u064a \u0628\u062f\u0648\u0646 \u0635\u0641\u062d\u0629 \u0637\u0648\u064a\u0644\u0629."'),
        'form_help' => json_decode('"\u0623\u062f\u062e\u0644 \u0628\u064a\u0627\u0646\u0627\u062a \u0627\u0644\u0633\u062c\u0644 \u062b\u0645 \u0627\u062d\u0641\u0638 \u0627\u0644\u062a\u063a\u064a\u064a\u0631\u0627\u062a."'),
        'cancel' => json_decode('"\u0625\u0644\u063a\u0627\u0621"'),
        'step' => json_decode('"\u0627\u0644\u062e\u0637\u0648\u0629"'),
        'data' => json_decode('"\u0627\u0644\u0628\u064a\u0627\u0646\u0627\u062a"'),
        'fields' => json_decode('"\u062d\u0642\u0648\u0644"'),
        'saved_file' => json_decode('"\u064a\u0648\u062c\u062f \u0645\u0644\u0641 \u0645\u062d\u0641\u0648\u0638 \u0644\u0647\u0630\u0627 \u0627\u0644\u062d\u0642\u0644 \u0648\u064a\u0645\u0643\u0646 \u0627\u0633\u062a\u0628\u062f\u0627\u0644\u0647 \u0628\u0631\u0641\u0639 \u0645\u0644\u0641 \u062c\u062f\u064a\u062f."'),
        'previous' => json_decode('"\u0627\u0644\u0633\u0627\u0628\u0642"'),
        'save_next' => json_decode('"\u062d\u0641\u0638 \u0648\u0627\u0644\u0627\u0646\u062a\u0642\u0627\u0644 \u0644\u0644\u0642\u0633\u0645 \u0627\u0644\u062a\u0627\u0644\u064a"'),
        'save_changes' => json_decode('"\u062d\u0641\u0638 \u0627\u0644\u062a\u0639\u062f\u064a\u0644\u0627\u062a"'),
        'create_submit' => json_decode('"\u0625\u0636\u0627\u0641\u0629 \u0627\u0644\u0633\u062c\u0644"'),
        'new_store_title' => json_decode('"\u0625\u0646\u0634\u0627\u0621 \u0645\u062a\u062c\u0631 \u062c\u062f\u064a\u062f"'),
        'new_store_hint' => json_decode('"\u0627\u0628\u062f\u0623 \u0628\u0627\u0644\u0632\u0631 \u0627\u0644\u0639\u0644\u0648\u064a \u0644\u0641\u062a\u062d \u0646\u0645\u0648\u0630\u062c \u062a\u062f\u0631\u064a\u062c\u064a \u0642\u0635\u064a\u0631. \u0643\u0644 \u0642\u0633\u0645 \u064a\u0638\u0647\u0631 \u0645\u0646\u0641\u0635\u0644\u0627\u060c \u0648\u0628\u0639\u062f \u0625\u0643\u0645\u0627\u0644\u0647 \u062a\u0646\u062a\u0642\u0644 \u0645\u0628\u0627\u0634\u0631\u0629 \u0625\u0644\u0649 \u0627\u0644\u0642\u0633\u0645 \u0627\u0644\u062a\u0627\u0644\u064a."'),
        'edit' => json_decode('"\u062a\u0639\u062f\u064a\u0644"'),
        'readiness' => json_decode('"\u062c\u0627\u0647\u0632\u064a\u0629 \u0627\u0644\u0625\u0637\u0644\u0627\u0642"'),
        'readiness_hint' => json_decode('"\u0645\u0624\u0634\u0631 \u0633\u0631\u064a\u0639 \u0644\u0627\u0643\u062a\u0645\u0627\u0644 \u0623\u0647\u0645 \u0627\u0644\u0628\u064a\u0627\u0646\u0627\u062a \u0648\u0627\u0644\u0645\u0644\u0641\u0627\u062a \u0627\u0644\u0645\u0637\u0644\u0648\u0628\u0629."'),
        'current_score' => json_decode('"\u0627\u0644\u0646\u0633\u0628\u0629 \u0627\u0644\u062d\u0627\u0644\u064a\u0629"'),
        'not_ready' => json_decode('"\u063a\u064a\u0631 \u0645\u0643\u062a\u0645\u0644"'),
        'score_hint' => json_decode('"\u064a\u0634\u0645\u0644 \u0627\u0644\u0645\u0624\u0634\u0631: \u0627\u0644\u0627\u0633\u0645 \u0627\u0644\u062a\u062c\u0627\u0631\u064a\u060c \u0627\u0644\u0645\u0627\u0644\u0643\u060c \u0627\u0644\u0628\u0631\u064a\u062f\u060c \u0627\u0644\u062c\u0648\u0627\u0644\u060c \u0627\u0644\u062f\u0648\u0645\u064a\u0646\u060c \u0627\u0644\u0628\u0627\u0642\u0629\u060c \u0627\u0644\u062f\u0641\u0639\u060c \u0627\u0644\u0634\u062d\u0646\u060c \u0645\u0631\u062d\u0644\u0629 \u0627\u0644\u062a\u062c\u0647\u064a\u0632\u060c \u0627\u0644\u0633\u062c\u0644 \u0627\u0644\u062a\u062c\u0627\u0631\u064a\u060c \u0627\u0644\u0639\u0642\u062f."'),
        'ready_label' => json_decode('"\u062c\u0627\u0647\u0632 \u0644\u0644\u0625\u0637\u0644\u0627\u0642"'),
        'progress_label' => json_decode('"\u0642\u064a\u062f \u0627\u0644\u0627\u0633\u062a\u0643\u0645\u0627\u0644"'),
        'start_label' => json_decode('"\u0628\u062f\u0627\u064a\u0629 \u0627\u0644\u062a\u062c\u0647\u064a\u0632"'),
    ];
    $createLabelMap = [
        'stores' => $txt['new_store'],
        'subscriptions' => json_decode('"\u0625\u0646\u0634\u0627\u0621 \u0627\u0634\u062a\u0631\u0627\u0643 \u062c\u062f\u064a\u062f"'),
        'payments' => json_decode('"\u0625\u0636\u0627\u0641\u0629 \u0633\u062c\u0644 \u0645\u0627\u0644\u064A"'),
    ];
    $emptyTitleMap = [
        'stores' => $txt['new_store_title'],
        'subscriptions' => json_decode('"\u0625\u0646\u0634\u0627\u0621 \u0627\u0634\u062a\u0631\u0627\u0643 \u062c\u062f\u064a\u062f"'),
    ];
    $emptyHintMap = [
        'stores' => $txt['new_store_hint'],
        'subscriptions' => json_decode('"\u0627\u0628\u062f\u0623 \u0628\u0627\u0644\u0632\u0631 \u0627\u0644\u0639\u0644\u0648\u064a \u0644\u0641\u062a\u062d \u0645\u0639\u0627\u0644\u062c\u0629 \u0627\u0644\u0627\u0634\u062a\u0631\u0627\u0643 \u0639\u0644\u0649 \u062e\u0637\u0648\u0627\u062a. \u0643\u0644 \u0642\u0633\u0645 \u064a\u0638\u0647\u0631 \u0645\u0646\u0641\u0635\u0644\u0627\u060c \u0648\u0628\u0639\u062f \u0627\u0643\u062a\u0645\u0627\u0644\u0647 \u062a\u0646\u062a\u0642\u0644 \u0645\u0628\u0627\u0634\u0631\u0629 \u0625\u0644\u0649 \u0627\u0644\u0642\u0633\u0645 \u0627\u0644\u062a\u0627\u0644\u064a."'),
    ];
    $readinessTitleMap = [
        'stores' => $txt['readiness'],
        'subscriptions' => json_decode('"\u062c\u0627\u0647\u0632\u064a\u0629 \u0627\u0644\u0627\u0634\u062a\u0631\u0627\u0643"'),
    ];
    $readinessHintMap = [
        'stores' => $txt['readiness_hint'],
        'subscriptions' => json_decode('"\u0645\u0624\u0634\u0631 \u0633\u0631\u064a\u0639 \u0644\u0627\u0643\u062a\u0645\u0627\u0644 \u0627\u0644\u0628\u0627\u0642\u0629 \u0648\u0627\u0644\u062f\u0641\u0639 \u0648\u0627\u0644\u062a\u062c\u062f\u064a\u062f \u0648\u0645\u0644\u0641\u0627\u062a \u0627\u0644\u062a\u0639\u0627\u0642\u062f."'),
    ];
    $scoreHintMap = [
        'stores' => $txt['score_hint'],
        'subscriptions' => json_decode('"\u064a\u0634\u0645\u0644 \u0627\u0644\u0645\u0624\u0634\u0631: \u0627\u0633\u0645 \u0627\u0644\u0645\u062a\u062c\u0631\u060c \u0635\u0627\u062d\u0628 \u0627\u0644\u062d\u0633\u0627\u0628\u060c \u0627\u0644\u0628\u0627\u0642\u0629\u060c \u0627\u0644\u062d\u0627\u0644\u0629\u060c \u0627\u0644\u0641\u0648\u062a\u0631\u0629\u060c \u0627\u0644\u062a\u062c\u062f\u064a\u062f\u060c \u0637\u0631\u064a\u0642\u0629 \u0627\u0644\u062f\u0641\u0639\u060c \u062d\u0627\u0644\u0629 \u0627\u0644\u0641\u0627\u062a\u0648\u0631\u0629\u060c \u0645\u0624\u0634\u0631 \u0635\u062d\u0629 \u0627\u0644\u062d\u0633\u0627\u0628\u060c \u0627\u0644\u0639\u0642\u062f\u060c \u0648\u0627\u0644\u0641\u0627\u062a\u0648\u0631\u0629."'),
    ];
    $readinessFieldsMap = [
        'stores' => ['name', 'owner', 'owner_email', 'owner_phone', 'domain', 'plan', 'payment_gateway', 'shipping_partner', 'onboarding_stage'],
        'subscriptions' => ['store', 'owner', 'owner_email', 'owner_phone', 'account_manager', 'plan', 'status', 'billing_cycle', 'renewal_date', 'payment_method', 'invoice_status', 'health_score'],
    ];
    $fileFieldsMap = [
        'stores' => ['commercial_register_file', 'contract_file'],
        'subscriptions' => ['contract_file', 'invoice_file'],
    ];
    $createButtonLabel = $createLabelMap[$sectionKey] ?? $txt['create_record'];
    $emptyTitle = $emptyTitleMap[$sectionKey] ?? $txt['create_record'];
    $emptyHint = $emptyHintMap[$sectionKey] ?? $txt['form_help'];
    $readinessTitle = $readinessTitleMap[$sectionKey] ?? $txt['readiness'];
    $readinessHint = $readinessHintMap[$sectionKey] ?? $txt['readiness_hint'];
    $scoreHint = $scoreHintMap[$sectionKey] ?? $txt['score_hint'];
    $pageMap = [
        'Store Management' => json_decode('"\u0625\u062F\u0627\u0631\u0629 \u0627\u0644\u0645\u062A\u0627\u062C\u0631"'),
        'Step-by-step onboarding wizard for a professional store launch.' => json_decode('"\u0646\u0645\u0648\u0630\u062C \u062A\u062F\u0631\u064A\u062C\u064A \u0644\u0625\u0646\u0634\u0627\u0621 \u0648\u062A\u0634\u063A\u064A\u0644 \u0645\u062A\u062C\u0631 \u0627\u062D\u062A\u0631\u0627\u0641\u064A."'),
        'Subscriptions' => json_decode('"\u0625\u062F\u0627\u0631\u0629 \u0627\u0644\u0627\u0634\u062A\u0631\u0627\u0643\u0627\u062A"'),
        'Structured subscription lifecycle with billing, renewals, and account health.' => json_decode('"\u0625\u062F\u0627\u0631\u0629 \u0627\u062D\u062A\u0631\u0627\u0641\u064A\u0629 \u0644\u062F\u0648\u0631\u0629 \u0627\u0644\u0627\u0634\u062A\u0631\u0627\u0643 \u0645\u0646 \u0627\u0644\u0641\u0648\u062A\u0631\u0629 \u0625\u0644\u0649 \u0627\u0644\u062A\u062C\u062F\u064A\u062F \u0648\u0645\u0624\u0634\u0631\u0627\u062A \u0635\u062D\u0629 \u0627\u0644\u062D\u0633\u0627\u0628."'),
        'Payments' => json_decode('"\u0625\u062F\u0627\u0631\u0629 \u0627\u0644\u0645\u062F\u0641\u0648\u0639\u0627\u062A"'),
        'Shipping' => json_decode('"\u0625\u062F\u0627\u0631\u0629 \u0627\u0644\u0634\u062D\u0646"'),
        'Carrier operations, escalation, and service continuity.' => json_decode('"\u062A\u0634\u063A\u064A\u0644 \u0634\u0631\u0643\u0627\u062A \u0627\u0644\u0634\u062D\u0646 \u0648\u0627\u0644\u062A\u0635\u0639\u064A\u062F \u0648\u0627\u0633\u062A\u0645\u0631\u0627\u0631\u064A\u0629 \u0627\u0644\u062E\u062F\u0645\u0629."'),
        'Analytics' => json_decode('"\u0627\u0644\u062A\u062D\u0644\u064A\u0644\u0627\u062A"'),
        'Reporting operations, publishing, and refresh cycles.' => json_decode('"\u0625\u062F\u0627\u0631\u0629 \u0627\u0644\u062A\u0642\u0627\u0631\u064A\u0631 \u0648\u0627\u0644\u0646\u0634\u0631 \u0648\u062F\u0648\u0631\u0627\u062A \u062A\u062D\u062F\u064A\u062B \u0627\u0644\u0628\u064A\u0627\u0646\u0627\u062A."'),
        'Partners' => json_decode('"\u0627\u0644\u0634\u0631\u0643\u0627\u0621"'),
        'Partner delivery readiness, capacity, and approvals.' => json_decode('"\u062C\u0627\u0647\u0632\u064A\u0629 \u0627\u0644\u0634\u0631\u0643\u0627\u0621 \u0648\u0627\u0644\u0633\u0639\u0629 \u0648\u0627\u0644\u0627\u0639\u062A\u0645\u0627\u062F\u0627\u062A."'),
        'Support' => json_decode('"\u0627\u0644\u062F\u0639\u0645 \u0627\u0644\u0641\u0646\u064A"'),
        'Support operations with assignment, escalation, and SLA tracking.' => json_decode('"\u0625\u062F\u0627\u0631\u0629 \u0627\u0644\u062F\u0639\u0645 \u0645\u0639 \u0627\u0644\u062A\u0648\u0632\u064A\u0639 \u0648\u0627\u0644\u062A\u0635\u0639\u064A\u062F \u0648\u0645\u062A\u0627\u0628\u0639\u0629 SLA."'),
        'Apps' => json_decode('"\u0627\u0644\u062A\u0637\u0628\u064A\u0642\u0627\u062A"'),
        'Release operations, health monitoring, and rollback readiness.' => json_decode('"\u062A\u0634\u063A\u064A\u0644 \u0627\u0644\u0625\u0635\u062F\u0627\u0631\u0627\u062A \u0648\u0645\u0631\u0627\u0642\u0628\u0629 \u0627\u0644\u0635\u062D\u0629 \u0648\u062C\u0627\u0647\u0632\u064A\u0629 \u0627\u0644\u062A\u0631\u0627\u062C\u0639."'),
        'Settings' => json_decode('"\u0627\u0644\u0625\u0639\u062F\u0627\u062F\u0627\u062A"'),
        'Configuration operations, reviews, and production controls.' => json_decode('"\u062A\u0634\u063A\u064A\u0644 \u0627\u0644\u0625\u0639\u062F\u0627\u062F\u0627\u062A \u0648\u0627\u0644\u0645\u0631\u0627\u062C\u0639\u0627\u062A \u0648\u0627\u0644\u062A\u062D\u0643\u0645 \u0628\u0627\u0644\u0625\u0646\u062A\u0627\u062C."'),
        'Financial operations, invoices, revenue visibility, and AI-assisted payment health.' => json_decode('"\u0645\u0644\u0641 \u0645\u0627\u0644\u064A \u0645\u062A\u0643\u0627\u0645\u0644 \u0644\u0625\u062F\u0627\u0631\u0629 \u0627\u0644\u062F\u062E\u0644 \u0648\u0627\u0644\u0641\u0648\u0627\u062A\u064A\u0631 \u0648\u0631\u0624\u0649 \u0627\u0644\u0630\u0643\u0627\u0621 \u{0644}\u{0635}\u{062D}\u{0629} \u{0627}\u{0644}\u{0645}\u{062F}\u{0641}\u{0648}\u{0639}\u{0627}\u{062A}."'),
    ];
    $summaryLabelMap = [
        'Total Records' => json_decode('"\u0625\u062C\u0645\u0627\u0644\u064A \u0627\u0644\u0633\u062C\u0644\u0627\u062A"'),
        'Active' => json_decode('"\u0627\u0644\u0633\u062C\u0644\u0627\u062A \u0627\u0644\u0646\u0634\u0637\u0629"'),
        'Last Update' => json_decode('"\u0622\u062E\u0631 \u062A\u062D\u062F\u064A\u062B"'),
    ];
    $summaryChangeMap = [
        'Updated' => json_decode('"\u0645\u062D\u062F\u062B\u0629"'),
        'Current status' => json_decode('"\u062D\u0633\u0628 \u0627\u0644\u062D\u0627\u0644\u0629 \u0627\u0644\u062D\u0627\u0644\u064A\u0629"'),
        'Section feed' => json_decode('"\u062E\u0644\u0627\u0635\u0629 \u0627\u0644\u0642\u0633\u0645"'),
    ];
    $tableTitleMap = [
        'Store Management Ledger' => json_decode('"\u0633\u062C\u0644 \u0625\u062F\u0627\u0631\u0629 \u0627\u0644\u0645\u062A\u0627\u062C\u0631"'),
        'Subscriptions Ledger' => json_decode('"\u0633\u062C\u0644 \u0627\u0644\u0627\u0634\u062A\u0631\u0627\u0643\u0627\u062A"'),
        'Payments Ledger' => json_decode('"\u0633\u062C\u0644 \u0627\u0644\u0645\u062F\u0641\u0648\u0639\u0627\u062A \u0648\u0627\u0644\u0641\u0648\u0627\u062A\u064A\u0631"'),
        'Shipping Ledger' => json_decode('"\u0633\u062C\u0644 \u0627\u0644\u0634\u062D\u0646"'),
        'Analytics Ledger' => json_decode('"\u0633\u062C\u0644 \u0627\u0644\u062A\u062D\u0644\u064A\u0644\u0627\u062A"'),
        'Partners Ledger' => json_decode('"\u0633\u062C\u0644 \u0627\u0644\u0634\u0631\u0643\u0627\u0621"'),
        'Support Ledger' => json_decode('"\u0633\u062C\u0644 \u0627\u0644\u062F\u0639\u0645 \u0627\u0644\u0641\u0646\u064A"'),
        'Apps Ledger' => json_decode('"\u0633\u062C\u0644 \u0627\u0644\u062A\u0637\u0628\u064A\u0642\u0627\u062A"'),
        'Settings Ledger' => json_decode('"\u0633\u062C\u0644 \u0627\u0644\u0625\u0639\u062F\u0627\u062F\u0627\u062A"'),
    ];
    $tableDescMap = ['Editable and exportable records.' => json_decode('"\u0633\u062C\u0644\u0627\u062A \u0642\u0627\u0628\u0644\u0629 \u0644\u0644\u062A\u0639\u062F\u064A\u0644 \u0648\u0627\u0644\u062A\u0635\u062F\u064A\u0631."')];
    $columnMap = [
        'Primary' => json_decode('"\u0627\u0644\u0628\u064A\u0627\u0646\u0627\u062A \u0627\u0644\u0623\u0633\u0627\u0633\u064A\u0629"'),
        'Details' => json_decode('"\u062A\u0641\u0627\u0635\u064A\u0644 \u0625\u0636\u0627\u0641\u064A\u0629"'),
        'Status' => json_decode('"\u0627\u0644\u062D\u0627\u0644\u0629"'),
        'Value' => json_decode('"\u0627\u0644\u0642\u064A\u0645\u0629"'),
        'Actions' => json_decode('"\u0627\u0644\u0625\u062C\u0631\u0627\u0621\u0627\u062A"'),
    ];
    $panelTitleMap = [
        'Latest Updates' => json_decode('"\u0622\u062E\u0631 \u0627\u0644\u062A\u062D\u062F\u064A\u062B\u0627\u062A"'),
        json_decode('"\u062A\u062C\u062F\u064A\u062F\u0627\u062A \u0642\u0631\u064A\u0628\u0629"') => json_decode('"\u062A\u062C\u062F\u064A\u062F\u0627\u062A \u0642\u0631\u064A\u0628\u0629"'),
        json_decode('"\u0645\u0624\u0634\u0631\u0627\u062A \u0627\u0644\u0641\u0648\u062A\u0631\u0629"') => json_decode('"\u0645\u0624\u0634\u0631\u0627\u062A \u0627\u0644\u0641\u0648\u062A\u0631\u0629"'),
    ];
    $alertToneMap = [
        'danger' => 'border-rose-200 bg-rose-50 text-rose-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
        'info' => 'border-sky-200 bg-sky-50 text-sky-700',
    ];
    $pricingTxt = [
        'title' => json_decode('"\u0644\u0648\u062D\u0629 \u0627\u0644\u0628\u0627\u0642\u0627\u062A"'),
        'desc' => json_decode('"\u0645\u0642\u0627\u0631\u0646\u0629 \u0633\u0631\u064A\u0639\u0629 \u0644\u0628\u0627\u0642\u0627\u062A \u0627\u0644\u0627\u0634\u062A\u0631\u0627\u0643 \u0644\u062A\u0633\u0647\u064A\u0644 \u0627\u0644\u0628\u064A\u0639 \u0648\u0627\u0644\u062A\u0648\u0635\u064A\u0629."'),
        'featured' => json_decode('"\u0627\u0644\u0623\u0643\u062B\u0631 \u0637\u0644\u0628\u0627"'),
    ];
    $workspaceTxt = [
        'title' => json_decode('"\u0645\u0633\u0627\u0631\u0627\u062A \u0627\u0644\u0639\u0645\u0644"'),
        'desc' => json_decode('"\u0627\u062E\u062A\u0631 \u0627\u0644\u0645\u0633\u0627\u0631 \u0627\u0644\u0630\u064A \u062A\u0631\u064A\u062F \u0627\u0644\u0639\u0645\u0644 \u0639\u0644\u064A\u0647 \u0641\u0642\u0637 \u0644\u062A\u0642\u0644\u064A\u0644 \u0627\u0644\u062A\u0634\u062A\u062A \u0648\u062A\u0631\u062A\u064A\u0628 \u0627\u0644\u0648\u0627\u062C\u0647\u0629."'),
    ];
    $workspacePanelsBySection = [
        'payments' => [
            'overview' => ['label' => json_decode('"\u0646\u0638\u0631\u0629 \u0639\u0627\u0645\u0629"'), 'hint' => json_decode('"\u0627\u0644\u0645\u0644\u062E\u0635 \u0648\u0627\u0644\u062A\u0646\u0628\u064A\u0647\u0627\u062A"')],
            'finance' => ['label' => json_decode('"\u0627\u0644\u0645\u0644\u0641 \u0627\u0644\u0645\u0627\u0644\u064A"'), 'hint' => json_decode('"\u0627\u0644\u062F\u062E\u0644 \u0648\u0627\u0644\u062A\u0633\u0648\u064A\u0627\u062A"')],
            'ai' => ['label' => json_decode('"AI Insights"'), 'hint' => json_decode('"\u0627\u0644\u0645\u062E\u0627\u0637\u0631 \u0648\u0627\u0644\u062A\u0648\u0635\u064A\u0627\u062A"')],
            'invoice' => ['label' => json_decode('"\u0627\u0644\u0641\u0627\u062A\u0648\u0631\u0629"'), 'hint' => json_decode('"\u0645\u0639\u0627\u064A\u0646\u0629 \u0648\u0637\u0628\u0627\u0639\u0629"')],
            'records' => ['label' => json_decode('"\u0627\u0644\u0633\u062C\u0644"'), 'hint' => json_decode('"\u0627\u0644\u0628\u062D\u062B \u0648\u0627\u0644\u062C\u062F\u0648\u0644"')],
            'form' => ['label' => json_decode('"\u0625\u062F\u0627\u0631\u0629 \u0627\u0644\u0633\u062C\u0644"'), 'hint' => json_decode('"\u0625\u0636\u0627\u0641\u0629 \u0623\u0648 \u062A\u0639\u062F\u064A\u0644"')],
        ],
        'shipping' => [
            'overview' => ['label' => json_decode('"\u0646\u0638\u0631\u0629 \u0639\u0627\u0645\u0629"'), 'hint' => json_decode('"\u0645\u0624\u0634\u0631\u0627\u062A \u0627\u0644\u0623\u062F\u0627\u0621 \u0648\u0635\u062D\u0629 \u0627\u0644\u062A\u0634\u063A\u064A\u0644"')],
            'operations' => ['label' => json_decode('"\u0627\u0644\u062A\u0634\u063A\u064A\u0644"'), 'hint' => json_decode('"\u0627\u0644\u0625\u0631\u0633\u0627\u0644 \u0648\u0627\u0644\u062A\u0635\u0639\u064A\u062F"')],
            'records' => ['label' => json_decode('"\u0627\u0644\u0633\u062C\u0644"'), 'hint' => json_decode('"\u0627\u0644\u0628\u062D\u062B \u0648\u0627\u0644\u062C\u062F\u0648\u0644"')],
            'form' => ['label' => json_decode('"\u0627\u0644\u0646\u0645\u0648\u0630\u062C"'), 'hint' => json_decode('"\u0625\u0636\u0627\u0641\u0629 \u0623\u0648 \u062A\u0639\u062F\u064A\u0644"')],
        ],
        'analytics' => [
            'overview' => ['label' => json_decode('"\u0646\u0638\u0631\u0629 \u0639\u0627\u0645\u0629"'), 'hint' => json_decode('"\u062D\u0627\u0644\u0629 \u0627\u0644\u062A\u0642\u0627\u0631\u064A\u0631"')],
            'operations' => ['label' => json_decode('"\u0627\u0644\u062A\u0634\u063A\u064A\u0644"'), 'hint' => json_decode('"\u0627\u0644\u0646\u0634\u0631 \u0648\u062A\u062D\u062F\u064A\u062B \u0627\u0644\u0628\u064A\u0627\u0646\u0627\u062A"')],
            'records' => ['label' => json_decode('"\u0627\u0644\u0633\u062C\u0644"'), 'hint' => json_decode('"\u0627\u0644\u0628\u062D\u062B \u0648\u0627\u0644\u062C\u062F\u0648\u0644"')],
            'form' => ['label' => json_decode('"\u0627\u0644\u0646\u0645\u0648\u0630\u062C"'), 'hint' => json_decode('"\u0625\u0636\u0627\u0641\u0629 \u0623\u0648 \u062A\u0639\u062F\u064A\u0644"')],
        ],
        'partners' => [
            'overview' => ['label' => json_decode('"\u0646\u0638\u0631\u0629 \u0639\u0627\u0645\u0629"'), 'hint' => json_decode('"\u0644\u0645\u062D\u0629 \u0639\u0646 \u0627\u0644\u0633\u0639\u0629 \u0648\u0627\u0644\u062C\u0627\u0647\u0632\u064A\u0629"')],
            'operations' => ['label' => json_decode('"\u0627\u0644\u062A\u0634\u063A\u064A\u0644"'), 'hint' => json_decode('"\u0627\u0644\u0627\u0639\u062A\u0645\u0627\u062F \u0648\u0627\u0644\u0625\u064A\u0642\u0627\u0641"')],
            'records' => ['label' => json_decode('"\u0627\u0644\u0633\u062C\u0644"'), 'hint' => json_decode('"\u0627\u0644\u0628\u062D\u062B \u0648\u0627\u0644\u062C\u062F\u0648\u0644"')],
            'form' => ['label' => json_decode('"\u0627\u0644\u0646\u0645\u0648\u0630\u062C"'), 'hint' => json_decode('"\u0625\u0636\u0627\u0641\u0629 \u0623\u0648 \u062A\u0639\u062F\u064A\u0644"')],
        ],
        'support' => [
            'overview' => ['label' => json_decode('"\u0646\u0638\u0631\u0629 \u0639\u0627\u0645\u0629"'), 'hint' => json_decode('"\u0637\u0627\u0628\u0648\u0631 \u0627\u0644\u062A\u0630\u0627\u0643\u0631 \u0648SLA"')],
            'operations' => ['label' => json_decode('"\u0627\u0644\u062A\u0634\u063A\u064A\u0644"'), 'hint' => json_decode('"\u0627\u0644\u062A\u0648\u0632\u064A\u0639 \u0648\u0627\u0644\u0625\u063A\u0644\u0627\u0642"')],
            'records' => ['label' => json_decode('"\u0627\u0644\u0633\u062C\u0644"'), 'hint' => json_decode('"\u0627\u0644\u0628\u062D\u062B \u0648\u0627\u0644\u062C\u062F\u0648\u0644"')],
            'form' => ['label' => json_decode('"\u0627\u0644\u0646\u0645\u0648\u0630\u062C"'), 'hint' => json_decode('"\u0625\u0636\u0627\u0641\u0629 \u0623\u0648 \u062A\u0639\u062F\u064A\u0644"')],
        ],
        'apps' => [
            'overview' => ['label' => json_decode('"\u0646\u0638\u0631\u0629 \u0639\u0627\u0645\u0629"'), 'hint' => json_decode('"\u0635\u062D\u0629 \u0627\u0644\u0625\u0635\u062F\u0627\u0631"')],
            'operations' => ['label' => json_decode('"\u0627\u0644\u062A\u0634\u063A\u064A\u0644"'), 'hint' => json_decode('"\u0627\u0644\u0625\u0637\u0644\u0627\u0642 \u0648\u0627\u0644\u062A\u0631\u0627\u062C\u0639"')],
            'records' => ['label' => json_decode('"\u0627\u0644\u0633\u062C\u0644"'), 'hint' => json_decode('"\u0627\u0644\u0628\u062D\u062B \u0648\u0627\u0644\u062C\u062F\u0648\u0644"')],
            'form' => ['label' => json_decode('"\u0627\u0644\u0646\u0645\u0648\u0630\u062C"'), 'hint' => json_decode('"\u0625\u0636\u0627\u0641\u0629 \u0623\u0648 \u062A\u0639\u062F\u064A\u0644"')],
        ],
        'settings' => [
            'overview' => ['label' => json_decode('"\u0646\u0638\u0631\u0629 \u0639\u0627\u0645\u0629"'), 'hint' => json_decode('"\u062D\u0627\u0644\u0629 \u0627\u0644\u0648\u062D\u062F\u0627\u062A"')],
            'operations' => ['label' => json_decode('"\u0627\u0644\u062A\u0634\u063A\u064A\u0644"'), 'hint' => json_decode('"\u0627\u0644\u062A\u0641\u0639\u064A\u0644 \u0648\u0627\u0644\u0645\u0631\u0627\u062C\u0639\u0629"')],
            'records' => ['label' => json_decode('"\u0627\u0644\u0633\u062C\u0644"'), 'hint' => json_decode('"\u0627\u0644\u0628\u062D\u062B \u0648\u0627\u0644\u062C\u062F\u0648\u0644"')],
            'form' => ['label' => json_decode('"\u0627\u0644\u0646\u0645\u0648\u0630\u062C"'), 'hint' => json_decode('"\u0625\u0636\u0627\u0641\u0629 \u0623\u0648 \u062A\u0639\u062F\u064A\u0644"')],
        ],
    ];
    $workspacePanels = $workspacePanelsBySection[$sectionKey] ?? [];
    $currentPanel = $isWorkspaceSection ? request()->query('panel', ($form['editingRecord'] ? 'form' : '')) : 'default';
    if ($isWorkspaceSection && $currentPanel !== '' && ! array_key_exists($currentPanel, $workspacePanels)) { $currentPanel = ''; }
    $panelQueryBase = array_merge(request()->query(), ['edit' => null]);
    $workspacePanelLinks = collect($workspacePanels)->mapWithKeys(fn ($panel, $key) => [$key => route($activeRoute, array_filter(array_merge($panelQueryBase, ['panel' => $key]), fn ($value) => $value !== null && $value !== ''))])->all();
    $contentGridClass = 'xl:grid-cols-1';
    $showOverview = ! $isWorkspaceSection || $currentPanel === 'overview';
    $showOperations = ! $isWorkspaceSection || $currentPanel === 'operations';
    $showFinance = ! $isWorkspaceSection || $currentPanel === 'finance';
    $showAi = ! $isWorkspaceSection || $currentPanel === 'ai';
    $showInvoice = ! $isWorkspaceSection || $currentPanel === 'invoice';
    $showRecords = ! $isWorkspaceSection || $currentPanel === 'records';
    $showForm = ! $isWorkspaceSection || $currentPanel === 'form';
    $financeTxt = [
        'title' => json_decode('"\u0627\u0644\u0645\u0644\u0641 \u0627\u0644\u0645\u0627\u0644\u064A"'),
        'desc' => json_decode('"\u0645\u0644\u062E\u0635 \u0644\u0644\u062F\u062E\u0644 \u0648\u0627\u0644\u0635\u0627\u0641\u064A \u0648\u0627\u0644\u0636\u0631\u0627\u0626\u0628 \u0648\u0627\u0644\u062A\u0633\u0648\u064A\u0627\u062A."'),
        'ledger' => json_decode('"\u062C\u062F\u0648\u0644 \u0627\u0644\u0623\u0631\u0642\u0627\u0645 \u0627\u0644\u0645\u0627\u0644\u064A\u0629"'),
        'ai_title' => json_decode('"\u0631\u0624\u0649 \u0627\u0644\u0630\u0643\u0627\u0621 \u0644\u0644\u0645\u062F\u0641\u0648\u0639\u0627\u062A"'),
        'ai_desc' => json_decode('"\u062A\u062D\u0644\u064A\u0644 \u062A\u0634\u063A\u064A\u0644\u064A \u0633\u0631\u064A\u0639 \u064A\u0628\u0646\u064A \u0639\u0644\u0649 \u0627\u0644\u0641\u0634\u0644 \u0648\u0627\u0644\u0645\u062E\u0627\u0637\u0631 \u0648\u0627\u0644\u062A\u062D\u0635\u064A\u0644."'),
        'invoice_title' => json_decode('"\u0645\u0639\u0627\u064A\u0646\u0629 \u0627\u0644\u0641\u0627\u062A\u0648\u0631\u0629"'),
        'invoice_desc' => json_decode('"\u0628\u0627\u0631\u0643\u0648\u062F \u0645\u0648\u0644\u062F \u062F\u0627\u062E\u0644\u064A\u0627 \u0644\u0644\u062A\u062D\u0642\u0642 \u0648\u0627\u0644\u0623\u0631\u0634\u0641\u0629."'),
        'invoice_customer' => json_decode('"\u0627\u0644\u0639\u0645\u064A\u0644"'),
        'invoice_amount' => json_decode('"\u0642\u064A\u0645\u0629 \u0627\u0644\u0641\u0627\u062A\u0648\u0631\u0629"'),
        'invoice_tax' => json_decode('"\u0627\u0644\u0636\u0631\u064A\u0628\u0629"'),
        'invoice_due' => json_decode('"\u0627\u0644\u0627\u0633\u062A\u062D\u0642\u0627\u0642"'),
        'risk' => json_decode('"\u0645\u062E\u0627\u0637\u0631"'),
    ];
@endphp

<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <p class="text-sm font-bold text-brand-600">Solve Admin</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-900">{{ $pageMap[$pageTitle] ?? $pageTitle }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-8 text-slate-500">{{ $pageMap[$pageDescription] ?? $pageDescription }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ $form['exportUrl'] }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700">{{ $txt['export'] }}</a>
            @if ($isWorkspaceSection)
                <a href="{{ $workspacePanelLinks['form'] }}" class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white">{{ $createButtonLabel }}</a>
            @elseif ($usesWizard && ! $form['editingRecord'])
                <button type="button" @click="openForm()" class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white">{{ $createButtonLabel }}</button>
            @endif
        </div>
    </div>
</section>

@if (session('status'))
    <section class="mt-6 rounded-[28px] border border-emerald-100 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700 shadow-card">
        {{ session('status') }}
    </section>
@endif

@if (session('provisioning.store_id'))
    <section class="mt-4 rounded-[28px] border border-sky-100 bg-sky-50 px-5 py-4 text-sm font-bold text-sky-800 shadow-card">
        <p>تم تجهيز حساب التاجر وربطه بالمتجر: {{ session('provisioning.store_id') }}.</p>
        <p class="mt-2 text-xs text-sky-700">المستخدم: {{ session('provisioning.username') }} @if (session('provisioning.temporary_password')) | كلمة مرور مؤقتة: {{ session('provisioning.temporary_password') }} @endif</p>
    </section>
@endif

@if ($errors->any())
    <section class="mt-6 rounded-[28px] border border-rose-100 bg-rose-50 px-5 py-4 text-sm text-rose-700 shadow-card">
        <p class="font-bold">{{ $txt['form_error'] }}</p>
    </section>
@endif

@if ($isWorkspaceSection)
    <section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-2xl font-extrabold text-slate-900">{{ $workspaceTxt['title'] }}</h3>
                <p class="mt-2 text-sm text-slate-500">{{ $workspaceTxt['desc'] }}</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                @foreach ($workspacePanels as $key => $panel)
                    <a href="{{ $workspacePanelLinks[$key] }}" class="rounded-[24px] border px-4 py-4 text-right transition {{ $currentPanel === $key ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                        <span class="block text-sm font-extrabold">{{ $panel['label'] }}</span>
                        <span class="mt-2 block text-xs font-bold opacity-80">{{ $panel['hint'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @if ($currentPanel === '')
        <section class="mt-6 rounded-[32px] border border-dashed border-slate-200 bg-white/70 p-8 shadow-card">
            <h3 class="text-2xl font-extrabold text-slate-900">{{ json_decode('"\u0627\u062E\u062A\u0631 \u0645\u0633\u0627\u0631\u0627 \u0645\u0646 \u0627\u0644\u0623\u0639\u0644\u0649"') }}</h3>
            <p class="mt-3 max-w-3xl text-sm leading-8 text-slate-500">{{ json_decode('"\u0644\u0627 \u064A\u062A\u0645 \u0639\u0631\u0636 \u0623\u064A \u0645\u062D\u062A\u0648\u0649 \u0641\u064A \u0647\u0630\u0627 \u0627\u0644\u0642\u0633\u0645 \u062D\u062A\u0649 \u062A\u0636\u063A\u0637 \u0639\u0644\u0649 \u0627\u0644\u0632\u0631 \u0627\u0644\u062E\u0627\u0635 \u0628\u0627\u0644\u0645\u0633\u0627\u0631 \u0627\u0644\u0645\u0637\u0644\u0648\u0628."') }}</p>
        </section>
    @endif
@endif

@if (! $isWorkspaceSection || $currentPanel !== '')
<section class="mt-6 grid gap-6 {{ $contentGridClass }}">
    <div class="space-y-6">
        @if ($showOverview && ! empty($alerts))
            <section class="grid gap-4 md:grid-cols-{{ min(count($alerts), 3) }}">
                @foreach ($alerts as $alert)
                    <div class="rounded-[28px] border p-5 shadow-card {{ $alertToneMap[$alert['tone']] ?? 'border-slate-200 bg-slate-50 text-slate-700' }}">
                        <p class="text-sm font-extrabold">{{ $alert['title'] }}</p>
                        <p class="mt-2 text-sm leading-7">{{ $alert['description'] }}</p>
                        @if (! empty($alert['items']))
                            <div class="mt-4 space-y-2 text-xs font-bold">
                                @foreach ($alert['items'] as $item)
                                    <p>{{ $item }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        @if ($showFinance && ! empty($financeDesk))
            <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                <div>
                    <h3 class="text-2xl font-extrabold text-slate-900">{{ $financeTxt['title'] }}</h3>
                    <p class="mt-2 text-sm text-slate-500">{{ $financeTxt['desc'] }}</p>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach (($financeDesk['headline'] ?? []) as $metric)
                        <div class="rounded-[24px] bg-slate-50 p-5">
                            <p class="text-sm font-bold text-slate-500">{{ $metric['label'] }}</p>
                            <p class="mt-3 text-2xl font-extrabold text-slate-900">{{ $metric['value'] }}</p>
                        </div>
                    @endforeach
                </div>
                @if (! empty($financeDesk['ledger']))
                    <div class="mt-6 rounded-[28px] bg-slate-950 p-6 text-white">
                        <p class="text-sm font-bold text-slate-300">{{ $financeTxt['ledger'] }}</p>
                        <div class="mt-4 grid gap-3 md:grid-cols-3">
                            @foreach ($financeDesk['ledger'] as $entry)
                                <div class="rounded-[22px] bg-white/5 p-4">
                                    <p class="text-xs font-bold text-slate-300">{{ $entry['label'] }}</p>
                                    <p class="mt-2 text-xl font-extrabold">{{ $entry['value'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endif

        @if ($showAi && ! empty($aiInsights))
            <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                <div>
                    <h3 class="text-2xl font-extrabold text-slate-900">{{ $financeTxt['ai_title'] }}</h3>
                    <p class="mt-2 text-sm text-slate-500">{{ $financeTxt['ai_desc'] }}</p>
                </div>
                <div class="mt-6 grid gap-4 xl:grid-cols-3">
                    @foreach ($aiInsights as $insight)
                        <article class="rounded-[26px] border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <h4 class="text-lg font-extrabold text-slate-900">{{ $insight['title'] }}</h4>
                                <span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-bold text-white">{{ $financeTxt['risk'] }} {{ $insight['score'] }}</span>
                            </div>
                            <p class="mt-4 text-sm leading-7 text-slate-600">{{ $insight['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if (! empty($pricingPlans))
            <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-2xl font-extrabold text-slate-900">{{ $pricingTxt['title'] }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $pricingTxt['desc'] }}</p>
                    </div>
                </div>
                <div class="mt-6 grid gap-4 xl:grid-cols-3">
                    @foreach ($pricingPlans as $plan)
                        <article class="rounded-[28px] border p-5 {{ $plan['featured'] ? 'border-brand-500 bg-brand-50/60 shadow-card' : 'border-slate-200 bg-slate-50' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-xl font-extrabold text-slate-900">{{ $plan['name'] }}</h4>
                                    <p class="mt-2 text-sm text-slate-500">{{ $plan['audience'] }}</p>
                                </div>
                                @if ($plan['featured'])
                                    <span class="rounded-full bg-brand-600 px-3 py-1 text-xs font-bold text-white">{{ $pricingTxt['featured'] }}</span>
                                @endif
                            </div>
                            <div class="mt-5 flex items-end gap-2">
                                <p class="text-3xl font-extrabold text-slate-900">{{ $plan['price'] }}</p>
                                <p class="pb-1 text-sm text-slate-500">/ {{ $plan['cycle'] }}</p>
                            </div>
                            <div class="mt-5 space-y-2 text-sm text-slate-700">
                                @foreach ($plan['features'] as $feature)
                                    <p>{{ $feature }}</p>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($showRecords)
        <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
            <form method="GET" class="grid gap-4 lg:grid-cols-[minmax(0,1fr),220px,auto] lg:items-end">
                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">{{ $txt['search'] }}</label>
                    <input type="text" name="q" value="{{ $filters['search'] }}" placeholder="{{ $filters['placeholder'] }}" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-brand-400 focus:bg-white">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">{{ $txt['status'] }}</label>
                    <select name="status" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-brand-400 focus:bg-white">
                        @foreach ($filters['statusOptions'] as $option)
                            <option value="{{ $option }}" @selected($filters['status'] === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white">{{ $txt['apply'] }}</button>
            </form>
        </section>
        @endif

        @if ($showForm)
        <section x-show="{{ $usesWizard && ! $form['editingRecord'] ? 'currentStep >= 0' : 'true' }}" x-cloak class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-2xl font-extrabold text-slate-900">{{ $form['editingRecord'] ? $txt['edit_record'] : $txt['create_record'] }}</h3>
                    <p class="mt-2 text-sm text-slate-500">{{ $usesWizard ? $txt['wizard_help'] : $txt['form_help'] }}</p>
                </div>
                <a href="{{ $form['cancelUrl'] }}" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700">{{ $txt['cancel'] }}</a>
            </div>

            <form id="managed-record-form" method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="mt-6 space-y-6">
                @csrf
                <input type="hidden" name="q" value="{{ $filters['search'] }}">
                <input type="hidden" name="current_status" value="{{ $filters['status'] }}">

                @if ($usesWizard)
                    <div class="grid gap-3 md:grid-cols-{{ max($wizardGroups->count(), 1) }}">
                        @foreach ($wizardGroups as $index => $group)
                            <button type="button" @click="goTo({{ $index }})" class="rounded-2xl border px-4 py-3 text-right transition" :class="currentStep === {{ $index }} ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-200 bg-slate-50 text-slate-500'">
                                <span class="block text-xs font-bold">{{ $txt['step'] }} {{ $index + 1 }}</span>
                                <span class="mt-2 block text-sm font-extrabold">{{ $group['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                @foreach ($wizardGroups->isNotEmpty() ? $wizardGroups : [['key' => 'default', 'label' => $txt['data'], 'description' => '', 'fields' => collect($form['fields'])]] as $index => $group)
                    <div x-show="{{ $usesWizard ? 'currentStep === ' . $index : 'true' }}" x-transition.opacity.duration.200ms class="rounded-[28px] border border-slate-200 bg-slate-50/70 p-6">
                        <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
                            <div>
                                <h4 class="text-2xl font-extrabold text-slate-900">{{ $group['label'] }}</h4>
                                @if ($group['description'])
                                    <p class="mt-2 text-sm text-slate-500">{{ $group['description'] }}</p>
                                @endif
                            </div>
                            <span class="rounded-full bg-white px-4 py-2 text-xs font-bold text-brand-600">{{ $group['fields']->count() }} {{ $txt['fields'] }}</span>
                        </div>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            @foreach ($group['fields'] as $field)
                                <div class="{{ ($field['span'] ?? null) === 'full' ? 'md:col-span-2' : '' }}">
                                    <label class="mb-2 block text-sm font-bold text-slate-700">{{ $field['label'] }}</label>
                                    @php
                                        $value = old($field['name'], $form['editingRecord'][$field['name']] ?? '');
                                        $type = $field['type'] ?? 'text';
                                    @endphp
                                    @if ($type === 'textarea')
                                        <textarea name="{{ $field['name'] }}" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-400">{{ $value }}</textarea>
                                    @elseif ($type === 'select')
                                        <select name="{{ $field['name'] }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-400">
                                            @foreach ($field['options'] as $option)
                                                <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    @elseif (in_array($type, ['file', 'image'], true))
                                        <input type="file" name="{{ $field['name'] }}" @change="captureFile($event, '{{ $field['name'] }}')" class="block w-full rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3 text-sm">
                                        @if (! empty($form['editingRecord'][$field['name']]))
                                            <p class="mt-2 text-xs text-slate-500">{{ $txt['saved_file'] }}</p>
                                        @endif
                                    @else
                                        <input type="{{ in_array($type, ['email', 'date', 'tel'], true) ? $type : 'text' }}" name="{{ $field['name'] }}" value="{{ $value }}" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-400">
                                    @endif
                                    @error($field['name'])
                                        <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        @if ($usesWizard)
                            <div class="mt-6 flex items-center justify-between gap-3">
                                <button type="button" @click="prev()" x-show="currentStep > 0" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700">{{ $txt['previous'] }}</button>
                                <div class="mr-auto"></div>
                                <button type="button" @click="next()" x-show="currentStep < lastStep" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-bold text-white">{{ $txt['save_next'] }}</button>
                                <button type="submit" x-show="currentStep === lastStep" class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white">{{ $form['editingRecord'] ? $txt['save_changes'] : $txt['create_submit'] }}</button>
                            </div>
                        @endif
                    </div>
                @endforeach

                @unless ($usesWizard)
                    <div class="flex justify-end gap-3">
                        <a href="{{ $form['cancelUrl'] }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700">{{ $txt['cancel'] }}</a>
                        <button type="submit" class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white">{{ $form['editingRecord'] ? $txt['save_changes'] : $txt['create_submit'] }}</button>
                    </div>
                @endunless
            </form>
        </section>
        @endif

        @if ($usesWizard && ! $form['editingRecord'] && ! $isWorkspaceSection)
            <section x-show="currentStep < 0" class="rounded-[32px] border border-dashed border-slate-200 bg-white/70 p-6 shadow-card">
                <h3 class="text-2xl font-extrabold text-slate-900">{{ $emptyTitle }}</h3>
                <p class="mt-3 max-w-2xl text-sm leading-8 text-slate-500">{{ $emptyHint }}</p>
            </section>
        @endif

        @if ($showInvoice && ! empty($invoicePreview))
            <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div class="max-w-xl">
                        <h3 class="text-2xl font-extrabold text-slate-900">{{ $financeTxt['invoice_title'] }}</h3>
                        <p class="mt-2 text-sm text-slate-500">{{ $financeTxt['invoice_desc'] }}</p>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-[24px] bg-slate-50 p-4">
                                <p class="text-xs font-bold text-slate-400">{{ $financeTxt['invoice_customer'] }}</p>
                                <p class="mt-2 font-extrabold text-slate-900">{{ $invoicePreview['customer'] }}</p>
                            </div>
                            <div class="rounded-[24px] bg-slate-50 p-4">
                                <p class="text-xs font-bold text-slate-400">{{ $financeTxt['invoice_amount'] }}</p>
                                <p class="mt-2 font-extrabold text-slate-900">{{ $invoicePreview['amount'] }}</p>
                            </div>
                            <div class="rounded-[24px] bg-slate-50 p-4">
                                <p class="text-xs font-bold text-slate-400">{{ $financeTxt['invoice_tax'] }}</p>
                                <p class="mt-2 font-extrabold text-slate-900">{{ $invoicePreview['tax'] }}</p>
                            </div>
                            <div class="rounded-[24px] bg-slate-50 p-4">
                                <p class="text-xs font-bold text-slate-400">{{ $financeTxt['invoice_due'] }}</p>
                                <p class="mt-2 font-extrabold text-slate-900">{{ $invoicePreview['due_date'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="w-full max-w-md rounded-[28px] border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold text-slate-400">{{ $invoicePreview['number'] }}</p>
                                <p class="mt-2 text-lg font-extrabold text-slate-900">{{ $invoicePreview['gateway'] }}</p>
                            </div>
                            <span class="rounded-full bg-brand-600 px-3 py-1 text-xs font-bold text-white">{{ $invoicePreview['status'] }}</span>
                        </div>
                        <div class="mt-5 overflow-hidden rounded-[20px] border border-slate-200 bg-white p-3">{!! $invoicePreview['barcode_svg'] !!}</div>
                        @if (! empty($invoicePreview['id']))
                            <a href="{{ route('admin.payments.invoice', ['recordId' => $invoicePreview['id']]) }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-bold text-white">{{ json_decode('"\u0637\u0628\u0627\u0639\u0629 / PDF"') }}</a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @if ($showRecords)
        <section class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-2xl font-extrabold text-slate-900">{{ $tableTitleMap[$table['title']] ?? $table['title'] }}</h3>
                    <p class="mt-2 text-sm text-slate-500">{{ $tableDescMap[$table['description']] ?? $table['description'] }}</p>
                </div>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-right">
                    <thead>
                        <tr class="text-xs font-bold uppercase tracking-wide text-slate-400">
                            @foreach ($table['columns'] as $column)
                                <th class="px-4 py-3">{{ $columnMap[$column] ?? $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($table['rows'] as $row)
                            <tr class="align-top">
                                @foreach ($row['cells'] as $cell)
                                    <td class="px-4 py-4">
                                        @if (isset($cell['badge']))
                                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ $cell['badge'] }}</span>
                                        @else
                                            <p class="font-bold text-slate-900">{{ $cell['primary'] }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ $cell['secondary'] }}</p>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        @if ($sectionKey === 'orders')
                                            <a href="{{ route('admin.orders.show', ['order' => $row['id']]) }}" class="rounded-xl bg-brand-50 px-3 py-2 text-xs font-bold text-brand-700">Timeline</a>
                                        @endif
                                        @if ($sectionKey === 'customers')
                                            <a href="{{ route('admin.customers.show', ['customer' => $row['id']]) }}" class="rounded-xl bg-brand-50 px-3 py-2 text-xs font-bold text-brand-700">CRM</a>
                                        @endif
                                        <a href="{{ route($activeRoute, ['edit' => $row['id']] + request()->query()) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700">{{ $txt['edit'] }}</a>
                                        @foreach ($row['actions'] as $action)
                                            @if (($action['kind'] ?? 'status') === 'link')
                                                <a href="{{ $action['value'] }}" target="_blank" rel="noopener noreferrer" class="rounded-xl px-3 py-2 text-xs font-bold {{ $action['classes'] }}">{{ str_starts_with($action['label'], 'Set ') ? json_decode('"\u062A\u0639\u064A\u064A\u0646 "') . Str::after($action['label'], 'Set ') : $action['label'] }}</a>
                                            @else
                                                <form method="POST" action="{{ route('admin.sections.update', ['section' => $sectionKey, 'recordId' => $row['id']]) }}">
                                                    @csrf
                                                    <input type="hidden" name="{{ ($action['kind'] ?? 'status') === 'action' ? 'action' : 'status' }}" value="{{ $action['value'] }}">
                                                    <input type="hidden" name="q" value="{{ $filters['search'] }}">
                                                    <input type="hidden" name="current_status" value="{{ $filters['status'] }}">
                                                    <button class="rounded-xl px-3 py-2 text-xs font-bold {{ $action['classes'] }}">{{ str_starts_with($action['label'], 'Set ') ? json_decode('"\u062A\u0639\u064A\u064A\u0646 "') . Str::after($action['label'], 'Set ') : $action['label'] }}</button>
                                                </form>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @endif
    </div>

</section>
@endif

<script>
    function storeWizard() {
        return {
            currentStep: {{ $form['editingRecord'] || $form['createOpen'] || ! $usesWizard ? 0 : -1 }},
            lastStep: {{ max($wizardGroups->count() - 1, 0) }},
            readinessFields: @js($readinessFieldsMap[$sectionKey] ?? []),
            fileFields: @js($fileFieldsMap[$sectionKey] ?? []),
            fileSummaries: [],
            openForm() { this.currentStep = 0; },
            goTo(step) { this.currentStep = step; },
            next() { if (this.currentStep < this.lastStep) this.currentStep++; },
            prev() { if (this.currentStep > 0) this.currentStep--; },
            captureFile(event, label) {
                const file = event.target.files[0];
                if (!file) return;
                const existing = this.fileSummaries.filter((item) => item.label !== label);
                existing.push({ label, name: file.name });
                this.fileSummaries = existing;
            },
            fieldValue(name) {
                const form = document.getElementById('managed-record-form');
                if (!form) return '';
                const field = form.querySelector(`[name="${name}"]`);
                return field ? (field.value || '').trim() : '';
            },
            readinessScore() {
                const form = document.getElementById('managed-record-form');
                if (!form) return 0;
                let completed = 0;
                const total = this.readinessFields.length + this.fileFields.length;
                this.readinessFields.forEach((name) => { if (this.fieldValue(name) !== '') completed++; });
                this.fileFields.forEach((name) => {
                    const field = form.querySelector(`[name="${name}"]`);
                    if (field && field.files && field.files.length > 0) completed++;
                });
                return Math.round((completed / total) * 100);
            },
            readinessLabel() {
                const score = this.readinessScore();
                if (score >= 85) return @js($txt['ready_label']);
                if (score >= 55) return @js($txt['progress_label']);
                return @js($txt['start_label']);
            },
        }
    }
</script>
</div>
@endsection

