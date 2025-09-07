<!DOCTYPE html>
<html>
<head>
    <title>Test Site Settings</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>Test Site Settings Form</h1>
    
    @if ($errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    @if (session('success'))
        <div style="color: green;">
            {{ session('success') }}
        </div>
    @endif
    
    <form action="/settings/site" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div>
            <label for="site_title">Site Title:</label><br>
            <input type="text" id="site_title" name="site_title" value="{{ old('site_title', $settings['site_title'] ?? '') }}" required>
        </div>
        
        <div>
            <label for="site_description">Site Description:</label><br>
            <textarea id="site_description" name="site_description">{{ old('site_description', $settings['site_description'] ?? '') }}</textarea>
        </div>
        
        <div>
            <label for="site_logo">Site Logo:</label><br>
            <input type="file" id="site_logo" name="site_logo" accept="image/*">
        </div>
        
        <div>
            <label for="site_favicon">Site Favicon:</label><br>
            <input type="file" id="site_favicon" name="site_favicon" accept="image/*">
        </div>
        
        <div>
            <button type="submit">Update Settings</button>
        </div>
    </form>
    
    <h2>Current Settings:</h2>
    <ul>
        <li>Title: {{ $settings['site_title'] ?? 'Not set' }}</li>
        <li>Description: {{ $settings['site_description'] ?? 'Not set' }}</li>
        <li>Logo: {{ $settings['site_logo'] ?? 'Not set' }}</li>
        <li>Favicon: {{ $settings['site_favicon'] ?? 'Not set' }}</li>
    </ul>
</body>
</html>
