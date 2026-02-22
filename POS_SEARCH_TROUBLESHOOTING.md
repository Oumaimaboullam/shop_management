# POS Search Functionality - Troubleshooting Guide

## Issue: Search Not Working in POS

This guide will help you diagnose and fix the search functionality in the Point of Sale system.

## Quick Fix Steps

### 1. Setup Sample Products
Access this URL in your browser to create sample products:
```
http://localhost/shop%20management/setup_sample_products.php
```

### 2. Test Search Functionality
Use the test tool to verify search is working:
```
http://localhost/shop%20management/simple_search_test.html
```

### 3. Login to System
Make sure you're logged in:
```
http://localhost/shop%20management/login.php
```
Default credentials: `admin` / `admin`

### 4. Access POS
```
http://localhost/shop%20management/sales/pos.php
```

## Common Issues and Solutions

### Issue 1: "Unauthorized" Error
**Cause**: User not logged in or session expired
**Solution**: 
- Login again at `/login.php`
- Check browser cookies are enabled
- Clear browser cache and try again

### Issue 2: No Products Found
**Cause**: No products in database
**Solution**:
- Run `setup_sample_products.php` to add sample products
- Check database connection in `config/database.php`

### Issue 3: Database Connection Error
**Cause**: MySQL database not running or incorrect credentials
**Solution**:
- Start MySQL service
- Verify database credentials in `config/database.php`
- Create database using `database.sql`

### Issue 4: Search Returns Empty Results
**Cause**: Products exist but search query has issues
**Solution**:
- Check browser console for JavaScript errors
- Verify API endpoint is accessible: `api/products/search.php`
- Check server error logs

## Debugging Tools

### 1. Browser Console
Open browser developer tools (F12) and check:
- Console tab for JavaScript errors
- Network tab for failed API requests
- Response status codes

### 2. Server Logs
Check these files for errors:
- PHP error log
- Apache/Nginx error log
- MySQL error log

### 3. Test Files
Use these test files to isolate issues:
- `simple_search_test.html` - Frontend search testing
- `test_search_step_by_step.php` - Backend API testing
- `debug_search.php` - Database query testing

## File Structure Check

Ensure these files exist and are accessible:
```
/api/products/search.php          - Main search API
/sales/pos.php                    - POS interface
/api/auth/check_session.php       - Session validation
/config/database.php              - Database configuration
/includes/functions.php           - Helper functions
```

## Database Tables Required

Make sure these tables exist:
- `articles` - Product information
- `stock` - Product quantities
- `users` - User authentication

## API Response Format

The search API should return:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "barcode": "123456789",
      "name": "Laptop Computer",
      "sale_price": "599.99",
      "wholesale": "499.99",
      "stock": "10"
    }
  ]
}
```

## Performance Tips

1. **Database Indexing**: Add indexes to frequently searched columns
2. **Limit Results**: API already limits to 20 results
3. **Caching**: Consider caching popular searches
4. **Debouncing**: Frontend should debounce rapid keystrokes

## Security Notes

- All API endpoints require authentication
- SQL queries use prepared statements
- Input is properly sanitized
- Session management is enforced

## Getting Help

If issues persist:
1. Check browser console errors
2. Verify server error logs
3. Test API endpoints directly
4. Confirm database connectivity
5. Ensure proper file permissions

## Testing Checklist

- [ ] User can login successfully
- [ ] Sample products exist in database
- [ ] Search API returns proper JSON
- [ ] Frontend displays search results
- [ ] Barcode scanning works
- [ ] Product selection adds to cart
- [ ] No JavaScript errors in console
