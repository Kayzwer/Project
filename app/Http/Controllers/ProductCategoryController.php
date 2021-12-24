<?php

namespace App\Http\Controllers;

use App\Product;
use App\ProductCategory;
use Illuminate\Http\Request;
use App\Http\Requests\ProductCategoryRequest;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(ProductCategory $model)
    {
        $categories = ProductCategory::paginate(25);

        return view('inventory.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('inventory.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductCategoryRequest $request, ProductCategory $category)
    {
        $category->create($request->all());

        return redirect()
            ->route('categories.index')
            ->withStatus('Category created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ProductCategory $category)
    {
        return view('inventory.categories.show', [
            'category' => $category,
            'products' => Product::where('product_category_id', $category->id)->paginate(25)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductCategory $category)
    {
        return view('inventory.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ProductCategoryRequest $request, ProductCategory $category)
    {
        $category->update($request->all());

        return redirect()
            ->route('categories.index')
            ->withStatus('Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductCategory $category)
    {
        $category->delete();

        return redirect()
            ->route('categories.index')
            ->withStatus('Category deleted successfully.');
    }

    public function export(Request $request)
    {
        $fileName = 'categories.csv';
        $categories = ProductCategory::all();
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );
        $columns = array('name', 'number_of_product', 'stock', 'defective_stock', 'average_price_of_product');
        $callback = function() use($categories, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach($categories as $category) {
                $row['name'] = $category->name;
                $row['number_of_product'] = count($category->products);
                $row['stock'] = $category->products->sum('stock');
                $row['defective_stock'] = $category->products->sum('stock_defective');
                $row['average_price_of_product'] = $category->products->avg('price');
                fputcsv($file, array($row['name'], $row['number_of_product'], $row['stock'], $row['defective_stock'], $row['average_price_of_product'] ));
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}
