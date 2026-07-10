@extends('layouts.app')
@section('header_title', __('messages.price_lists.title'))

@section('content')
<div class="container mx-auto px-4 max-w-7xl animate-fade-in">

    {{-- ترويسة الشاشة --}}
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#005B9F]/10 flex items-center justify-center text-[#005B9F]">
                <i class="fas fa-tags text-2xl"></i>
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ __('messages.price_lists.title') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('messages.price_lists.subtitle') }}</p>
            </div>
        </div>
        <a href="{{ route('price-lists.create') }}" class="px-6 py-2.5 bg-[#008A3B] text-white rounded-lg font-bold hover:bg-[#007030] transition-colors shadow-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> {{ __('messages.price_lists.add') }}
        </a>
    </div>

    {{-- شريط البحث --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <form action="{{ route('price-lists.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">{{ __('messages.quick_search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('messages.price_lists.f_code') }} / {{ __('messages.price_lists.f_name') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-[#008A3B] bg-gray-50">
            </div>
            <div class="flex gap-2">
                <a href="{{ route('price-lists.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50">{{ __('messages.filter.cancel') }}</a>
                <button type="submit" class="px-6 py-2 bg-[#005B9F] text-white rounded-lg text-sm font-bold hover:bg-blue-800 flex items-center gap-2">
                    <i class="fas fa-search"></i> {{ __('messages.common.apply') }}
                </button>
            </div>
        </form>
    </div>

    {{-- جدول القوائم --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-sm font-bold">
                        <th class="p-4">{{ __('messages.price_lists.f_code') }}</th>
                        <th class="p-4">{{ __('messages.price_lists.f_name') }}</th>
                        <th class="p-4">{{ __('messages.price_lists.f_currency') }}</th>
                        <th class="p-4 text-center">{{ __('messages.price_lists.col_items') }}</th>
                        <th class="p-4">{{ __('messages.price_lists.col_validity') }}</th>
                        <th class="p-4 text-center">{{ __('messages.price_lists.open') }}</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($priceLists as $list)
                        <tr class="hover:bg-blue-50/40 transition-colors group cursor-pointer"
                            onclick="openPriceListModal({{ $list->id }}, '{{ route('price-lists.data', $list) }}')">
                            <td class="p-4 font-mono font-bold text-[#005B9F]">{{ $list->code }}</td>
                            <td class="p-4 font-bold text-gray-900">{{ $list->name }}</td>
                            <td class="p-4 text-gray-600">{{ $list->default_currency }}</td>
                            <td class="p-4 text-center">
                                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-amber-50 text-amber-600">{{ $list->items_count }}</span>
                            </td>
                            <td class="p-4 text-gray-500 text-xs" dir="ltr">
                                {{ optional($list->valid_from)->format('Y-m-d') ?? '—' }} → {{ optional($list->valid_to)->format('Y-m-d') ?? '—' }}
                            </td>
                            <td class="p-4 text-center">
                                @if($list->status === 'active')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-green-50 text-green-600"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> {{ __('messages.price_lists.st_active') }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> {{ __('messages.price_lists.st_inactive') }}</span>
                                @endif
                            </td>
                            <td class="p-4" onclick="event.stopPropagation()">
                                <div class="flex items-center gap-2 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('price-lists.edit', $list) }}" class="p-2 rounded-lg text-gray-400 hover:text-[#008A3B] hover:bg-green-50" title="{{ __('messages.common.edit') }}"><i class="fas fa-pen"></i></a>
                                    <form action="{{ route('price-lists.destroy', $list) }}" method="POST" onsubmit="return confirm('{{ __('messages.price_lists.del_confirm') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50" title="{{ __('messages.common.delete') }}"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-12 text-center text-gray-400">
                            <i class="fas fa-tags text-4xl mb-3 opacity-30 block"></i>
                            {{ __('messages.price_lists.no_data') }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($priceLists->hasPages()) <div class="p-4 border-t border-gray-100">{{ $priceLists->links() }}</div> @endif
    </div>
</div>

{{-- ===== Modal بيانات قائمة الأسعار ===== --}}
<div id="plModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
    {{-- خلفية داكنة --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closePriceListModal()"></div>

    {{-- نافذة المحتوى --}}
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col animate-fade-in">

        {{-- ترويسة المودال --}}
        <div class="bg-[#1e293b] text-white rounded-t-2xl px-6 py-4 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center">
                    <i class="fas fa-tags text-lg"></i>
                </div>
                <div>
                    <div id="plModalCode" class="font-mono font-bold text-sm text-blue-300"></div>
                    <div id="plModalName" class="font-bold text-lg leading-tight"></div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="printPriceList()" class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg text-sm font-bold flex items-center gap-2 transition-colors">
                    <i class="fas fa-print"></i> طباعة
                </button>
                <a id="plModalEditBtn" href="#" class="px-4 py-2 bg-[#008A3B] hover:bg-[#007030] rounded-lg text-sm font-bold flex items-center gap-2 transition-colors">
                    <i class="fas fa-pen"></i> تعديل
                </a>
                <button onclick="closePriceListModal()" class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-white/10 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
        </div>

        {{-- بيانات الترويسة --}}
        <div class="px-6 py-4 border-b border-gray-100 grid grid-cols-2 md:grid-cols-4 gap-4 shrink-0 bg-gray-50/60">
            <div>
                <p class="text-xs text-gray-500 mb-0.5">العملة</p>
                <p id="plModalCurrency" class="font-bold text-gray-800 text-sm"></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-0.5">الحالة</p>
                <p id="plModalStatus" class="text-sm"></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-0.5">صالح من</p>
                <p id="plModalFrom" class="font-bold text-gray-800 text-sm" dir="ltr"></p>
            </div>
            <div>
                <p class="text-xs text-gray-500 mb-0.5">صالح حتى</p>
                <p id="plModalTo" class="font-bold text-gray-800 text-sm" dir="ltr"></p>
            </div>
        </div>

        {{-- جدول الأصناف --}}
        <div class="overflow-auto flex-1 px-6 py-4">
            <div id="plModalLoading" class="py-16 text-center text-gray-400">
                <i class="fas fa-spinner fa-spin text-3xl mb-3 block text-[#005B9F]"></i>
                <p class="text-sm">جاري التحميل...</p>
            </div>
            <table id="plModalTable" class="w-full text-right border-collapse hidden">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-xs font-bold">
                        <th class="p-3 w-10 text-center">#</th>
                        <th class="p-3 w-36">كود الصنف</th>
                        <th class="p-3">اسم الصنف / المنتج</th>
                        <th class="p-3 w-24 text-center">الوحدة</th>
                        <th class="p-3 w-36 text-left">السعر</th>
                    </tr>
                </thead>
                <tbody id="plModalBody" class="divide-y divide-gray-100 text-sm"></tbody>
            </table>
            <div id="plModalEmpty" class="hidden py-12 text-center text-gray-400">
                <i class="fas fa-box-open text-3xl mb-2 block opacity-30"></i>
                <p class="text-sm">لا توجد أصناف في هذه القائمة</p>
            </div>
        </div>

        {{-- تذييل المودال --}}
        <div id="plModalFooter" class="px-6 py-3 border-t border-gray-100 bg-gray-50/60 rounded-b-2xl flex items-center justify-between shrink-0">
            <span id="plModalCount" class="text-xs text-gray-500"></span>
            <span id="plModalCurrencyFooter" class="text-xs text-gray-400"></span>
        </div>
    </div>
</div>

<script>
    // عناوين أساسية تُحقن من Laravel — تعمل من أي subfolder
    const PRICELISTS_URL = @json(url('price-lists'));

    let _currentPL = null;

    function openPriceListModal(id, url) {
        const modal = document.getElementById('plModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // إعادة ضبط المحتوى
        document.getElementById('plModalLoading').classList.remove('hidden');
        document.getElementById('plModalTable').classList.add('hidden');
        document.getElementById('plModalEmpty').classList.add('hidden');
        document.getElementById('plModalBody').innerHTML = '';

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                _currentPL = data;

                // ترويسة
                document.getElementById('plModalCode').textContent = data.code;
                document.getElementById('plModalName').textContent = data.name;
                document.getElementById('plModalCurrency').textContent = data.default_currency || '—';
                document.getElementById('plModalFrom').textContent = data.valid_from || '—';
                document.getElementById('plModalTo').textContent = data.valid_to || '—';
                document.getElementById('plModalCurrencyFooter').textContent = 'العملة: ' + (data.default_currency || '—');

                // الحالة
                const stEl = document.getElementById('plModalStatus');
                if (data.status === 'active') {
                    stEl.innerHTML = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-green-50 text-green-600"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> نشطة</span>';
                } else {
                    stEl.innerHTML = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> غير نشطة</span>';
                }

                // زر التعديل
                document.getElementById('plModalEditBtn').href = PRICELISTS_URL + '/' + data.id + '/edit';

                // الأصناف
                document.getElementById('plModalLoading').classList.add('hidden');
                if (!data.items || !data.items.length) {
                    document.getElementById('plModalEmpty').classList.remove('hidden');
                    document.getElementById('plModalCount').textContent = '0 صنف';
                    return;
                }

                const tbody = document.getElementById('plModalBody');
                data.items.forEach(function (item, idx) {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-blue-50/30';
                    tr.innerHTML = `
                        <td class="p-3 text-center text-gray-400 text-xs">${idx + 1}</td>
                        <td class="p-3 font-mono font-bold text-[#005B9F] text-xs">${item.code}</td>
                        <td class="p-3 font-medium text-gray-800">${item.name}</td>
                        <td class="p-3 text-center text-gray-500 text-xs">${item.uom}</td>
                        <td class="p-3 font-bold text-[#008A3B] text-left" dir="ltr">${item.price} <span class="text-xs font-normal text-gray-400">${data.default_currency}</span></td>`;
                    tbody.appendChild(tr);
                });

                document.getElementById('plModalTable').classList.remove('hidden');
                document.getElementById('plModalCount').textContent = data.items.length + ' صنف';
            })
            .catch(function () {
                document.getElementById('plModalLoading').innerHTML = '<p class="text-red-500 text-sm">حدث خطأ أثناء التحميل</p>';
            });
    }

    function closePriceListModal() {
        const modal = document.getElementById('plModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        _currentPL = null;
    }

    function printPriceList() {
        if (!_currentPL) return;
        const w = window.open('', '_blank', 'width=900,height=700');
        if (!w) { alert('فضلاً اسمح بالنوافذ المنبثقة'); return; }

        const rows = (_currentPL.items || []).map(function (item, idx) {
            return `<tr class="${idx % 2 ? 'alt' : ''}">
                <td class="num">${idx + 1}</td>
                <td class="code">${item.code}</td>
                <td>${item.name}</td>
                <td class="center">${item.uom}</td>
                <td class="price" dir="ltr">${item.price} ${_currentPL.default_currency}</td>
            </tr>`;
        }).join('');

        const status = _currentPL.status === 'active' ? 'نشطة' : 'غير نشطة';
        const validFrom = _currentPL.valid_from || '—';
        const validTo   = _currentPL.valid_to   || '—';

        const _logoUrl = '{{ asset("images/EFC-.png") }}';
        const _brand   = '{{ __("messages.app_name") }}';
        const _sub     = '{{ __("messages.app_sub") }}';

        w.document.write(`<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>قائمة أسعار — ${_currentPL.code}</title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
@page { margin: 14mm; }
* { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
body { font-family: 'Cairo', Arial, sans-serif; color: #1e293b; margin: 0; font-size: 13px; }
.sheet { max-width: 800px; margin: 0 auto; }
.head { display: flex; justify-content: space-between; align-items: center;
    padding: 14px 22px; background: linear-gradient(135deg, #005B9F, #008A3B); color: #fff; border-radius: 10px; }
.logo-wrap { display: flex; align-items: center; gap: 10px; }
.logo-wrap img { height: 46px; width: auto; object-fit: contain; }
.brand { font-size: 14px; font-weight: 800; line-height: 1.2; }
.brand small { display: block; font-size: 10px; font-weight: 400; opacity: .85; margin-top: 2px; }
.doc { text-align: left; }
.doc .t { font-size: 17px; font-weight: 700; }
.doc .s { font-size: 11px; opacity: .85; margin-top: 2px; }
.meta { display: flex; gap: 20px; flex-wrap: wrap; margin: 12px 0 16px;
    padding: 10px 14px; background: #f1f5f9; border-radius: 8px; font-size: 12px; color: #475569; }
.meta b { color: #0f172a; }
table { width: 100%; border-collapse: separate; border-spacing: 0;
    border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
thead tr { background: #1e293b; color: #fff; }
th { padding: 10px 12px; font-size: 12px; font-weight: 700; }
td { padding: 9px 12px; font-size: 12px; border-bottom: 1px solid #eef2f7; }
tr:last-child td { border-bottom: none; }
tr.alt td { background: #f8fafc; }
.num, .center { text-align: center; }
.code { font-family: monospace; color: #005B9F; font-weight: 700; }
.price { text-align: left; font-weight: 700; color: #008A3B; }
.foot { margin-top: 20px; font-size: 10px; color: #94a3b8; text-align: center;
    border-top: 1px solid #e2e8f0; padding-top: 8px; }
</style>
</head>
<body>
<div class="sheet">
    <div class="head">
        <div class="logo-wrap">
            <img src="${_logoUrl}" onerror="this.style.display='none'">
            <div class="brand">${_brand}${_sub ? `<small>${_sub}</small>` : ''}</div>
        </div>
        <div class="doc"><div class="t">قائمة أسعار</div><div class="s">${_currentPL.code} — ${_currentPL.name}</div></div>
    </div>
    <div class="meta">
        <span>العملة: <b>${_currentPL.default_currency}</b></span>
        <span>الحالة: <b>${status}</b></span>
        <span>صالحة من: <b dir="ltr">${validFrom}</b></span>
        <span>صالحة حتى: <b dir="ltr">${validTo}</b></span>
        <span>عدد الأصناف: <b>${(_currentPL.items || []).length}</b></span>
    </div>
    <table>
        <thead><tr>
            <th class="num" style="width:40px">#</th>
            <th style="width:130px">كود الصنف</th>
            <th>اسم الصنف / المنتج</th>
            <th class="center" style="width:90px">الوحدة</th>
            <th class="price" style="width:140px">السعر</th>
        </tr></thead>
        <tbody>${rows || '<tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px">لا توجد أصناف</td></tr>'}</tbody>
    </table>
    <div class="foot">EFC — نظام إدارة الموارد — تم الطباعة بتاريخ ${new Date().toLocaleDateString('ar-EG')}</div>
</div>
</body></html>`);
        w.document.close();
        w.focus();
        setTimeout(function () { w.print(); }, 500);
    }

    // إغلاق بـ Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePriceListModal();
    });
</script>
@endsection
