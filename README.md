# Excel to CSV Converter - Disaster Data

A PHP-based web application that converts Excel files containing disaster data sheets (affected, assistance, evacuation) into separate CSV files.

## Features

- 📊 Upload Excel files (.xlsx, .xls)
- 🔄 Convert three specific sheets: **affected**, **assistance**, and **evacuation**
- 💾 Generate separate CSV files for each sheet
- 📥 Download converted CSV files
- 🎨 Modern, responsive UI with drag-and-drop support

## Requirements

- PHP >= 7.4
- Composer
- Web server (Apache/Nginx) or PHP built-in server

## Installation

1. **Install Composer dependencies:**
   ```bash
   composer install
   ```

2. **Set up directories:**
   The application will automatically create the following directories:
   - `uploads/` - For uploaded Excel files
   - `csv_output/` - For generated CSV files

3. **Configure (optional):**
   Edit `config.php` if you need to change:
   - Upload directory paths
   - Maximum file size
   - Allowed file extensions

## Usage

1. **Start the development server:**
   ```bash
   php -S localhost:8000
   ```

2. **Open your browser:**
   Navigate to `http://localhost:8000`

3. **Upload Excel file:**
   - Click "Choose Excel File" or drag and drop an Excel file
   - The file must contain three sheets named: `affected`, `assistance`, and `evacuation`
   - Click "Convert to CSV"

4. **Download CSV files:**
   - After conversion, download links will appear for each converted CSV file

## API Endpoint

### POST `/backend/api.php`

Uploads an Excel file and converts it to CSV files.

**Request:**
- Method: POST
- Content-Type: multipart/form-data
- Body: `excel_file` (file)

**Response:**
```json
{
    "success": true,
    "results": [
        {
            "sheet": "affected",
            "filename": "affected_2024-01-15_10-30-45.csv",
            "path": "/path/to/csv_output/affected_2024-01-15_10-30-45.csv",
            "rows": 100,
            "columns": "E"
        },
        ...
    ],
    "errors": []
}
```

## File Structure

```
msit206/
├── backend/
│   └── api.php          # API endpoint for Excel processing
├── data/
│   └── disaster_data_latest.xlsx
├── uploads/             # Uploaded Excel files (auto-created)
├── csv_output/          # Generated CSV files (auto-created)
├── config.php           # Configuration file
├── index.php            # Main UI
├── composer.json        # PHP dependencies
└── README.md            # This file
```

## Notes

- The Excel file must contain sheets named exactly: `affected`, `assistance`, and `evacuation` (case-sensitive)
- Maximum file size: 10MB (configurable in `config.php`)
- CSV files are saved with timestamps to prevent overwriting
- The application uses PhpSpreadsheet library for Excel processing

## Troubleshooting

**Error: "PhpSpreadsheet library not found"**
- Run `composer install` to install dependencies

**Error: "Sheet 'affected' not found"**
- Ensure your Excel file has sheets named exactly: `affected`, `assistance`, `evacuation`

**Permission errors:**
- Ensure PHP has write permissions for `uploads/` and `csv_output/` directories

## License

MIT

