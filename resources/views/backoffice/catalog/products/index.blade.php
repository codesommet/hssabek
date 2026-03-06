<?php $page = 'products'; ?>
@extends('backoffice.layout.mainlayout')
@section('content')
    <!-- ========================
           Start Page Content
          ========================= -->

    <div class="page-wrapper">
        <!-- Start Container  -->
        <div class="content content-two">

            <!-- Start Breadcrumb -->
            <div class="d-flex d-block align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h6>Produits</h6>
                </div>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                    <div class="dropdown me-1">
                        <a href="javascript:void(0);" class="btn btn-outline-white d-inline-flex align-items-center"
                            data-bs-toggle="dropdown">
                            <i class="isax isax-export-1 me-1"></i>Exporter
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="javascript:void(0);">Télécharger en PDF</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0);">Télécharger en Excel</a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <a href="{{ route('bo.catalog.products.create') }}" class="btn btn-primary d-flex align-items-center"><i
                                class="isax isax-add-circle5 me-1"></i>Nouveau produit</a>
                    </div>
                </div>
            </div>
            <!-- End Breadcrumb -->

            <!-- Start Table Search -->
            <div class="mb-3">

                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <form action="{{ route('bo.catalog.products.index') }}" method="GET" class="table-search d-flex align-items-center mb-0">
                            <div class="search-input">
                                <input type="text" name="search" class="form-control" placeholder="Rechercher un produit..." value="{{ request('search') }}">
                                <a href="javascript:void(0);" class="btn-searchset" onclick="this.closest('form').submit()"><i
                                        class="isax isax-search-normal fs-12"></i></a>
                                @if(request('category_id'))
                                    <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                                @endif
                                @if(request('status'))
                                    <input type="hidden" name="status" value="{{ request('status') }}">
                                @endif
                            </div>
                        </form>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="dropdown">
                            <a href="javascript:void(0);"
                                class="dropdown-toggle btn btn-outline-white d-inline-flex align-items-center"
                                data-bs-toggle="dropdown">
                                <i class="isax isax-filter me-1"></i>Catégorie : <span class="fw-normal ms-1">{{ $categories->firstWhere('id', request('category_id'))?->name ?? 'Toutes' }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a href="{{ route('bo.catalog.products.index', array_merge(request()->except('category_id', 'page'))) }}" class="dropdown-item">Toutes</a>
                                </li>
                                @foreach($categories as $category)
                                    <li>
                                        <a href="{{ route('bo.catalog.products.index', array_merge(request()->except('page'), ['category_id' => $category->id])) }}" class="dropdown-item">{{ $category->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="dropdown">
                            <a href="javascript:void(0);"
                                class="dropdown-toggle btn btn-outline-white d-inline-flex align-items-center"
                                data-bs-toggle="dropdown">
                                <i class="isax isax-sort me-1"></i>Statut : <span class="fw-normal ms-1">{{ request('status') === 'active' ? 'Actif' : (request('status') === 'inactive' ? 'Inactif' : 'Tous') }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a href="{{ route('bo.catalog.products.index', array_merge(request()->except('status', 'page'))) }}" class="dropdown-item">Tous</a>
                                </li>
                                <li>
                                    <a href="{{ route('bo.catalog.products.index', array_merge(request()->except('page'), ['status' => 'active'])) }}" class="dropdown-item">Actif</a>
                                </li>
                                <li>
                                    <a href="{{ route('bo.catalog.products.index', array_merge(request()->except('page'), ['status' => 'inactive'])) }}" class="dropdown-item">Inactif</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
            <!-- End Table Search -->

            <!-- Start Table List -->
            <div class="table-responsive">
                <table class="table table-nowrap datatable">
                    <thead class="thead-light">
                        <tr>
                            <th class="no-sort">
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th>Unité</th>
                            <th>Quantité</th>
                            <th>Prix de vente</th>
                            <th class="no-sort">Statut</th>
                            <th class="no-sort"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);"
                                            class="avatar avatar-sm rounded-circle me-2 flex-shrink-0">
                                            @if($product->getFirstMediaUrl('product_image'))
                                                <img src="{{ $product->getFirstMediaUrl('product_image') }}"
                                                    class="rounded-circle" alt="{{ $product->name }}">
                                            @else
                                                <span class="avatar avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                                                    {{ strtoupper(substr($product->name, 0, 1)) }}
                                                </span>
                                            @endif
                                        </a>
                                        <div>
                                            <h6 class="fs-14 fw-medium mb-0"><a href="{{ route('bo.catalog.products.edit', $product) }}">{{ $product->name }}</a></h6>
                                            <span class="fs-12 text-muted">{{ $product->code ?? $product->sku ?? '—' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td class="text-dark">{{ $product->unit?->name ?? '—' }}</td>
                                <td>{{ $product->quantity ?? 0 }}</td>
                                <td class="text-dark">{{ number_format($product->selling_price, 2, ',', ' ') }}</td>
                                <td>
                                    @if($product->is_active)
                                        <span class="badge badge-soft-success d-inline-flex align-items-center">Actif
                                            <i class="isax isax-tick-circle ms-1"></i>
                                        </span>
                                    @else
                                        <span class="badge badge-soft-danger d-inline-flex align-items-center">Inactif
                                            <i class="isax isax-close-circle ms-1"></i>
                                        </span>
                                    @endif
                                </td>
                                <td class="action-item">
                                    <a href="javascript:void(0);" data-bs-toggle="dropdown">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="{{ route('bo.catalog.products.edit', $product) }}"
                                                class="dropdown-item d-flex align-items-center"><i
                                                    class="isax isax-edit me-2"></i>Modifier</a>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('bo.catalog.products.destroy', $product) }}">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item d-flex align-items-center text-danger" type="submit"
                                                    onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')">
                                                    <i class="isax isax-trash me-2"></i>Supprimer
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- End Table List -->

            {{ $products->links() }}

        </div>
        <!-- End Container  -->

        @component('backoffice.components.footer')
        @endcomponent

    </div>
    <!-- ========================
           End Page Content
          ========================= -->
@endsection
