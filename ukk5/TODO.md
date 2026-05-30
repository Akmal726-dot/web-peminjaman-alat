# TODO: Modify Loan and Return Receipt PDFs to Include Two Pages

## Steps to Complete:
1. **Modify cetak_struk_peminjaman.php**: Update the PDF generation to create two pages with identical content. Add labels "Lembar Petugas" (Officer Copy) on the first page and "Lembar Customer" (Customer Copy) on the second page.
   - Add a label cell at the top of each page before the header.
   - Duplicate the content generation code for the second page after adding a new page.
2. **Modify cetak_struk_pengembalian.php**: Apply the same changes to the return receipt PDF.
   - Create a function for page content generation.
   - Generate two pages with labels.
3. **Test the changes**: Ensure both PDFs generate correctly with two pages.
4. **Verify functionality**: Check that the download and print options work with the two-page PDFs.

## Progress:
- [x] Step 1: Modify cetak_struk_peminjaman.php
- [x] Step 2: Test the changes for loan receipt
- [x] Step 3: Modify cetak_struk_pengembalian.php
- [x] Step 4: Test the changes for return receipt
- [x] Step 5: Verify functionality
