/**
 +------------------------------------------------------------------------------
 *加载地址 地区联动
 +------------------------------------------------------------------------------
 * @category    js/project
 * @package     carrier
 * @subpackage  Exception
 * @author      qfl <fulin.qu@witsion.com>
 * @version     1.0
 +------------------------------------------------------------------------------
 */
 var addr_list = new Array();
function Init(inID,pro,city,dis) {
    $.ajax({
        url: xmlUrl,
        success: function(xml) {
            bindProvince(inID, xml,pro);
            bindCity(inID, xml,city);
            bindCounty(inID, xml,dis);
        }
    });
}
function ProvinceChange(inID, ProvinceCode) {
    $.ajax({
        url: xmlUrl,
        success: function(xml) {
            $("#" + inID + "_selPickUpCity").find('option').remove();
            $("#" + inID + "_selPickUpCounty").find('option').remove();
            $("#" + inID + "_hfPickUpProvince").val(ProvinceCode);
            bindCity(inID, xml);
            $("#" + inID + "_hfPickUpCity").val($("#" + inID + "_selPickUpCity").val());
            bindCounty(inID, xml);
            $("#" + inID + "_hfPickUpCounty").val($("#" + inID + "_selPickUpCounty").val());
        }
    });
}
function CityChange(inID, CityCode) {
    $.ajax({
        url: xmlUrl,
        success: function(xml) {
            $("#" + inID + "_selPickUpCounty").find('option').remove();
            $("#" + inID + "_hfPickUpCity").val($("#" + inID + "_selPickUpCity").val());
            bindCounty(inID, xml);
            $("#" + inID + "_hfPickUpCounty").val($("#" + inID + "_selPickUpCounty").val());
        }
    });
}
function CountyChange(inID, CountyCode) {
    $("#" + inID + "_hfPickUpCounty").val(CountyCode);
}
function bindProvince(inID, xml,pro) {
    //清空城市
    $("#" + inID + "_selPickUpCity").find('option').remove();
    //清空区域
    $("#" + inID + "_selPickUpCounty").find('option').remove();

    $(xml).find("province").each(function() {
        bindSelect(inID, "_selPickUpProvince", $(this).attr("code"), $(this).attr("name"),pro);
    });
}
function bindCity(inID, xml,city) {
    //清空区域
    $("#" + inID + "_selPickUpCounty").find('option').remove();

    $(xml).find("province[code = '" + $("#" + inID + "_selPickUpProvince").val() + "'] > city").each(function() {
        bindSelect(inID, "_selPickUpCity", $(this).attr("code"), $(this).attr("name"),city);
    });
}
function bindCounty(inID, xml,dis) {
    $(xml).find("city[code='" + $("#" + inID + "_selPickUpCity").val() + "']>county").each(function() {
        bindSelect(inID, "_selPickUpCounty", $(this).attr("code"), $(this).attr("name"),dis);
    });
}
function bindSelect(inID, SelectID, code, name,selectValue) {
    var selected = '';
    if(selectValue==code)selected = ' selected';
    $("#" + inID + SelectID).append("<option value='" + code + "'"+selected+">" + name + "</option>");
}
